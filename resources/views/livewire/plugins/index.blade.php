<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;

new class extends Component {
    use WithFileUploads;

    public string $name;
    public int $data_stale_minutes = 60;
    public string $data_strategy = "polling";
    public string $polling_url;
    public string $polling_verb = "get";
    public $polling_header;
    public $polling_body;
    public array $plugins;
    public $zipFile;

    public array $native_plugins = [
        'markup' =>
            ['name' => 'Markup', 'flux_icon_name' => 'code-bracket', 'detail_view_route' => 'plugins.markup'],
        'api' =>
            ['name' => 'API', 'flux_icon_name' => 'braces', 'detail_view_route' => 'plugins.api'],
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'data_stale_minutes' => 'required|integer|min:1',
        'data_strategy' => 'required|string|in:polling,webhook,static',
        'polling_url' => 'required_if:data_strategy,polling|nullable|url',
        'polling_verb' => 'required|string|in:get,post',
        'polling_header' => 'nullable|string|max:255',
        'polling_body' => 'nullable|string',
    ];

    private function refreshPlugins(): void
    {
        $userPlugins = auth()->user()?->plugins?->map(function ($plugin) {
            return $plugin->toArray();
        })->toArray();

        $this->plugins = array_merge($this->native_plugins, $userPlugins ?? []);
    }

    public function mount(): void
    {
        $this->refreshPlugins();
    }

    public function addPlugin(): void
    {
        abort_unless(auth()->user() !== null, 403);
        $this->validate();

        \App\Models\Plugin::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => auth()->id(),
            'name' => $this->name,
            'data_stale_minutes' => $this->data_stale_minutes,
            'data_strategy' => $this->data_strategy,
            'polling_url' => $this->polling_url ?? null,
            'polling_verb' => $this->polling_verb,
            'polling_header' => $this->polling_header,
            'polling_body' => $this->polling_body,
        ]);

        $this->reset(['name', 'data_stale_minutes', 'data_strategy', 'polling_url', 'polling_verb', 'polling_header', 'polling_body']);
        $this->refreshPlugins();

        Flux::modal('add-plugin')->close();
    }

    public function seedExamplePlugins(): void
    {
//        \Artisan::call('db:seed', ['--class' => 'ExampleRecipesSeeder']);
        \Artisan::call(\App\Console\Commands\ExampleRecipesSeederCommand::class, ['user_id' => auth()->id()]);
    }

    /**
     * Extract a ZIP file using PharData as an alternative to ZipArchive
     *
     * @param string $zipFile Path to the ZIP file
     * @param string $extractTo Path to extract the ZIP file to
     * @throws \Exception If extraction fails
     */
    private function extractZipWithPurePhp(string $zipFile, string $extractTo): void
    {
        // Check if the phar extension is available
        if (!extension_loaded('phar')) {
            throw new \Exception('The phar extension is required for this extraction method.');
        }

        // Check if PharData class exists
        if (!class_exists('PharData')) {
            throw new \Exception('The PharData class is not available.');
        }

        // Read the ZIP file
        $zipData = file_get_contents($zipFile);
        if ($zipData === false) {
            throw new \Exception('Could not read the ZIP file.');
        }

        // Create a temporary file for processing
        $tempFile = tempnam(sys_get_temp_dir(), 'zip');
        if ($tempFile === false) {
            throw new \Exception('Could not create temporary file.');
        }

        if (file_put_contents($tempFile, $zipData) === false) {
            @unlink($tempFile);
            throw new \Exception('Could not write to temporary file.');
        }

        // Use PharData as an alternative to ZipArchive
        try {
            // Convert ZIP to TAR format (which PharData can handle without extensions)
            $tarFile = $tempFile . '.tar';
            $phar = new \PharData($tempFile);
            $phar->convertToData(\Phar::TAR);

            // Extract the TAR file
            $tarPhar = new \PharData($tarFile);
            $tarPhar->extractTo($extractTo, null, true);

            // Clean up temporary files
            @unlink($tempFile);
            @unlink($tarFile);
        } catch (\Exception $e) {
            // Clean up temporary files
            @unlink($tempFile);
            @unlink($tarFile ?? '');

            throw new \Exception('Could not extract the ZIP file: ' . $e->getMessage());
        }
    }

    /**
     * Extract a ZIP file manually as a last resort
     * This is a very basic implementation that can handle simple ZIP files
     *
     * @param string $zipFile Path to the ZIP file
     * @param string $extractTo Path to extract the ZIP file to
     * @throws \Exception If extraction fails
     */
    private function extractZipManually(string $zipFile, string $extractTo): void
    {
        // Read the ZIP file
        $zipData = file_get_contents($zipFile);
        if ($zipData === false) {
            throw new \Exception('Could not read the ZIP file.');
        }

        // Create the src directory structure
        if (!file_exists($extractTo . '/src')) {
            if (!mkdir($extractTo . '/src', 0755, true)) {
                throw new \Exception('Could not create directory structure.');
            }
        }

        // Look for the required files in the ZIP data
        $fullLiquidContent = null;
        $settingsYamlContent = null;

        // This is a very simplified approach that looks for specific file signatures
        // It's not a complete ZIP parser, but it might work for simple ZIP files

        // Look for src/full.liquid
        $fullLiquidPos = strpos($zipData, 'src/full.liquid');
        if ($fullLiquidPos !== false) {
            // Try to extract the file content
            $startPos = strpos($zipData, "\x50\x4B\x03\x04", $fullLiquidPos - 30); // PK header
            if ($startPos !== false) {
                $fileNameLength = unpack('v', substr($zipData, $startPos + 26, 2))[1];
                $extraFieldLength = unpack('v', substr($zipData, $startPos + 28, 2))[1];
                $fileDataStart = $startPos + 30 + $fileNameLength + $extraFieldLength;
                $compressedSize = unpack('V', substr($zipData, $startPos + 18, 4))[1];
                $uncompressedSize = unpack('V', substr($zipData, $startPos + 22, 4))[1];
                $compressionMethod = unpack('v', substr($zipData, $startPos + 8, 2))[1];

                $fileData = substr($zipData, $fileDataStart, $compressedSize);

                // If the file is compressed, try to decompress it
                if ($compressionMethod === 8) { // DEFLATE
                    if (function_exists('gzinflate')) {
                        $fileData = gzinflate($fileData);
                    } else {
                        throw new \Exception('gzinflate function is not available.');
                    }
                }

                $fullLiquidContent = $fileData;
            }
        }

        // Look for src/settings.yml
        $settingsYamlPos = strpos($zipData, 'src/settings.yml');
        if ($settingsYamlPos !== false) {
            // Try to extract the file content
            $startPos = strpos($zipData, "\x50\x4B\x03\x04", $settingsYamlPos - 30); // PK header
            if ($startPos !== false) {
                $fileNameLength = unpack('v', substr($zipData, $startPos + 26, 2))[1];
                $extraFieldLength = unpack('v', substr($zipData, $startPos + 28, 2))[1];
                $fileDataStart = $startPos + 30 + $fileNameLength + $extraFieldLength;
                $compressedSize = unpack('V', substr($zipData, $startPos + 18, 4))[1];
                $uncompressedSize = unpack('V', substr($zipData, $startPos + 22, 4))[1];
                $compressionMethod = unpack('v', substr($zipData, $startPos + 8, 2))[1];

                $fileData = substr($zipData, $fileDataStart, $compressedSize);

                // If the file is compressed, try to decompress it
                if ($compressionMethod === 8) { // DEFLATE
                    if (function_exists('gzinflate')) {
                        $fileData = gzinflate($fileData);
                    } else {
                        throw new \Exception('gzinflate function is not available.');
                    }
                }

                $settingsYamlContent = $fileData;
            }
        }

        // Check if we found the required files
        if ($fullLiquidContent === null || $settingsYamlContent === null) {
            throw new \Exception('Could not find required files in the ZIP archive.');
        }

        // Write the extracted files
        if (file_put_contents($extractTo . '/src/full.liquid', $fullLiquidContent) === false) {
            throw new \Exception('Could not write full.liquid file.');
        }

        if (file_put_contents($extractTo . '/src/settings.yml', $settingsYamlContent) === false) {
            throw new \Exception('Could not write settings.yml file.');
        }
    }

    public function importZip(): void
    {
        abort_unless(auth()->user() !== null, 403);

        $this->validate([
            'zipFile' => 'required|file|mimes:zip|max:10240', // 10MB max
        ]);

        try {
            // Create a temporary directory
            $tempDir = storage_path('app/temp/' . uniqid('zip_', true));
            File::makeDirectory($tempDir, 0755, true);

            // Get the real path of the temporary file
            $zipFullPath = $this->zipFile->getRealPath();

            // Verify the file exists and is readable
            if (!file_exists($zipFullPath)) {
                throw new \Exception('Could not access the uploaded file. Temporary file does not exist at path: ' . $zipFullPath);
            }

            if (!is_readable($zipFullPath)) {
                throw new \Exception('Could not access the uploaded file. Temporary file exists but is not readable at path: ' . $zipFullPath);
            }

            // Log the file path for debugging
            \Illuminate\Support\Facades\Log::info('Processing ZIP file', [
                'path' => $zipFullPath,
                'size' => filesize($zipFullPath),
                'is_file' => is_file($zipFullPath),
                'permissions' => substr(sprintf('%o', fileperms($zipFullPath)), -4)
            ]);

            // Extract the ZIP file
            $extractionMethods = [];
            $extractionErrors = [];

            // Method 1: Try using ZipArchive if the extension is available
            if (class_exists('ZipArchive')) {
                try {
                    $extractionMethods[] = 'ZipArchive';
                    $zip = new ZipArchive();
                    if ($zip->open($zipFullPath) !== true) {
                        throw new \Exception('Could not open the ZIP file.');
                    }

                    $zip->extractTo($tempDir);
                    $zip->close();

                    // If we get here, extraction was successful
                    $extractionSuccess = true;
                } catch (\Exception $e) {
                    $extractionErrors['ZipArchive'] = $e->getMessage();
                    $extractionSuccess = false;
                }
            } else {
                $extractionErrors['ZipArchive'] = 'ZipArchive class not available.';
                $extractionSuccess = false;
            }

            // Method 2: If ZipArchive failed, try using the unzip command-line tool
            if (!isset($extractionSuccess) || !$extractionSuccess) {
                $extractionMethods[] = 'unzip command';
                $command = "unzip -q " . escapeshellarg($zipFullPath) . " -d " . escapeshellarg($tempDir);
                $returnVar = null;
                $output = null;

                exec($command, $output, $returnVar);

                if ($returnVar === 0) {
                    $extractionSuccess = true;
                } else {
                    $extractionErrors['unzip'] = 'Command returned error code: ' . $returnVar;
                    $extractionSuccess = false;
                }
            }

            // Method 3: If both ZipArchive and unzip failed, try a pure PHP solution with PharData
            if (!isset($extractionSuccess) || !$extractionSuccess) {
                $extractionMethods[] = 'PharData';
                try {
                    $this->extractZipWithPurePhp($zipFullPath, $tempDir);
                    $extractionSuccess = true;
                } catch (\Exception $e) {
                    $extractionErrors['PharData'] = $e->getMessage();
                    $extractionSuccess = false;
                }
            }

            // Method 4: Last resort - try a manual extraction approach
            if (!isset($extractionSuccess) || !$extractionSuccess) {
                $extractionMethods[] = 'Manual extraction';
                try {
                    $this->extractZipManually($zipFullPath, $tempDir);
                    $extractionSuccess = true;
                } catch (\Exception $e) {
                    $extractionErrors['Manual'] = $e->getMessage();
                    $extractionSuccess = false;
                }
            }

            // If all extraction methods failed, throw an exception with details
            if (!isset($extractionSuccess) || !$extractionSuccess) {
                $errorMsg = "Could not extract the ZIP file. Tried: " . implode(', ', $extractionMethods) . ". ";
                $errorMsg .= "Errors: " . json_encode($extractionErrors);
                throw new \Exception($errorMsg);
            }

            // Validate the structure
            if (!file_exists($tempDir . '/src/full.liquid') || !file_exists($tempDir . '/src/settings.yml')) {
                throw new \Exception('Invalid ZIP structure. Required files src/full.liquid and src/settings.yml are missing.');
            }

            // Parse settings.yml
            $settingsYaml = file_get_contents($tempDir . '/src/settings.yml');
            $settings = Yaml::parse($settingsYaml);

            // Read full.liquid content
            $fullLiquid = file_get_contents($tempDir . '/src/full.liquid');

            // Check if the file ends with .liquid to set markup language
            $markupLanguage = 'blade';
            if (pathinfo($tempDir . '/src/full.liquid', PATHINFO_EXTENSION) === 'liquid') {
                $markupLanguage = 'liquid';
            }

            // Ensure custom_fields is properly formatted
            if (!isset($settings['custom_fields']) || !is_array($settings['custom_fields'])) {
                $settings['custom_fields'] = [];
            }

            // Create configuration template with the custom fields
            $configurationTemplate = [
                'custom_fields' => $settings['custom_fields']
            ];

            // Create a new plugin
            $plugin = \App\Models\Plugin::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'user_id' => auth()->id(),
                'name' => $settings['name'] ?? 'Imported Plugin',
                'data_stale_minutes' => $settings['refresh_interval'] ?? 15,
                'data_strategy' => $settings['strategy'] ?? 'static',
                'polling_url' => $settings['polling_url'] ?? null,
                'polling_verb' => $settings['polling_verb'] ?? 'get',
                'markup_language' => $markupLanguage,
                'render_markup' => $fullLiquid,
                'configuration_template' => $configurationTemplate,
                'data_payload' => json_decode($settings['static_data'] ?? '{}', true),
            ]);

            // Clean up
            File::deleteDirectory($tempDir);

            $this->refreshPlugins();
            $this->reset(['zipFile']);

            Flux::modal('import-zip')->close();
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Plugin imported successfully!']);

        } catch (\Exception $e) {
            // Clean up on error
            if (isset($tempDir) && file_exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            $this->dispatch('notify', ['type' => 'error', 'message' => 'Error importing plugin: ' . $e->getMessage()]);
        }
    }

};
?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold dark:text-gray-100">Plugins &amp; Recipes</h2>

            <flux:button.group>
                <flux:modal.trigger name="add-plugin">
                    <flux:button icon="plus" variant="primary">Add Recipe</flux:button>
                </flux:modal.trigger>

                <flux:dropdown>
                    <flux:button icon="chevron-down" variant="primary"></flux:button>
                    <flux:menu>
                        <flux:modal.trigger name="import-zip">
                            <flux:menu.item icon="archive-box">Import ZIP File (trmnlp)</flux:menu.item>
                        </flux:modal.trigger>
                        <flux:menu.item icon="beaker" wire:click="seedExamplePlugins">Seed Example Recipes</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </flux:button.group>


        </div>

        <flux:modal name="import-zip" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Import ZIP File (trmnlp)</flux:heading>
                    <flux:subheading>Upload a ZIP file containing a TRMNL plugin</flux:subheading>
                </div>

                <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    <p>The ZIP file should contain the following structure:</p>
                    <pre class="mt-2 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs overflow-auto">
.
├── src
│   ├── full.liquid (required)
│   ├── settings.yml (required)
│   └── ...
└── ...
                    </pre>
                </div>

                <form wire:submit="importZip">
                    <div class="mb-4">
                        <label for="zipFile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ZIP File</label>
                        <input
                            type="file"
                            wire:model="zipFile"
                            id="zipFile"
                            accept=".zip"
                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 p-2.5"
                        />
                        @error('zipFile') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex">
                        <flux:spacer/>
                        <flux:button type="submit" variant="primary">Import Plugin</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        <flux:modal name="add-plugin" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Add Recipe</flux:heading>
                </div>

                <form wire:submit="addPlugin">
                    <div class="mb-4">
                        <flux:input label="Name" wire:model="name" id="name" class="block mt-1 w-full" type="text"
                                    name="name" autofocus/>
                    </div>

                    <div class="mb-4">
                        <flux:radio.group wire:model.live="data_strategy" label="Data Strategy" variant="segmented">
                            <flux:radio value="polling" label="Polling"/>
                            <flux:radio value="webhook" label="Webhook"/>
                            <flux:radio value="static" label="Static"/>
                        </flux:radio.group>
                    </div>

                    @if($data_strategy === 'polling')
                        <div class="mb-4">
                            <flux:input label="Polling URL" wire:model="polling_url" id="polling_url"
                                        placeholder="https://example.com/api"
                                        class="block mt-1 w-full" type="text" name="polling_url" autofocus/>
                        </div>

                        <div class="mb-4">
                            <flux:radio.group wire:model.live="polling_verb" label="Polling Verb" variant="segmented">
                                <flux:radio value="get" label="GET"/>
                                <flux:radio value="post" label="POST"/>
                            </flux:radio.group>
                        </div>

                        <div class="mb-4">
                            <flux:input label="Polling Header" wire:model="polling_header" id="polling_header"
                                        class="block mt-1 w-full" type="text" name="polling_header" autofocus/>
                        </div>

                        @if($polling_verb === 'post')
                        <div class="mb-4">
                            <flux:textarea
                                label="Polling Body"
                                wire:model="polling_body"
                                id="polling_body"
                                class="block mt-1 w-full font-mono"
                                name="polling_body"
                                rows="4"
                                placeholder=''
                            />
                        </div>
                        @endif
                        <div class="mb-4">
                            <flux:input label="Data is stale after minutes" wire:model.live="data_stale_minutes"
                                        id="data_stale_minutes"
                                        class="block mt-1 w-full" type="number" name="data_stale_minutes" autofocus/>
                        </div>
                    @endif

                    <div class="flex">
                        <flux:spacer/>
                        <flux:button type="submit" variant="primary">Create Recipe</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($plugins as $plugin)
                <div
                    class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-xs">
                    <a href="{{ ($plugin['detail_view_route']) ? route($plugin['detail_view_route']) : route('plugins.recipe', ['plugin' => $plugin['id']]) }}"
                       class="block">
                        <div class="flex items-center space-x-4 px-10 py-8">
                            <flux:icon name="{{$plugin['flux_icon_name'] ?? 'puzzle-piece'}}"
                                       class="text-4xl text-accent"/>
                            <h3 class="text-lg font-medium dark:text-zinc-200">{{$plugin['name']}}</h3>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
