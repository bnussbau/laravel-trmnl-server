<?php

namespace App\Models;

use App\Liquid\Filters\Data;
use App\Liquid\Filters\Localization;
use App\Liquid\Filters\Numbers;
use App\Liquid\Filters\StringMarkup;
use App\Liquid\Filters\Uniqueness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Keepsuit\Liquid\Exceptions\LiquidException;

class Plugin extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'data_payload' => 'json',
        'data_payload_updated_at' => 'datetime',
        'is_native' => 'boolean',
        'markup_language' => 'string',
        'configuration' => 'json',
        'configuration_template' => 'json',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function isDataStale(): bool
    {
        if ($this->data_strategy === 'webhook') {
            // Treat as stale if any webhook event has occurred in the past hour
            return $this->data_payload_updated_at && $this->data_payload_updated_at->gt(now()->subHour());
        }
        if (! $this->data_payload_updated_at || ! $this->data_stale_minutes) {
            return true;
        }

        return $this->data_payload_updated_at->addMinutes($this->data_stale_minutes)->isPast();
    }

    public function updateDataPayload(): void
    {
        if ($this->data_strategy === 'polling' && $this->polling_url) {

            $headers = ['User-Agent' => 'usetrmnl/byos_laravel', 'Accept' => 'application/json'];

            if ($this->polling_header) {
                $headerLines = explode("\n", trim($this->polling_header));
                foreach ($headerLines as $line) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        $headers[trim($parts[0])] = trim($parts[1]);
                    }
                }
            }

            $httpRequest = Http::withHeaders($headers);

            if ($this->polling_verb === 'post' && $this->polling_body) {
                $httpRequest = $httpRequest->withBody($this->polling_body);
            }

            // Resolve Liquid variables in the polling URL
            $resolvedUrl = $this->resolveLiquidVariables($this->polling_url);

            // Make the request based on the verb
            if ($this->polling_verb === 'post') {
                $response = $httpRequest->post($resolvedUrl)->json();
            } else {
                $response = $httpRequest->get($resolvedUrl)->json();
            }

            $this->update([
                'data_payload' => $response,
                'data_payload_updated_at' => now(),
            ]);
        }
    }

    /**
     * Resolve Liquid variables in a template string using the Liquid template engine
     *
     * @param string $template The template string containing Liquid variables
     * @return string The resolved template with variables replaced with their values
     * @throws LiquidException
     */
    public function resolveLiquidVariables(string $template): string
    {
        // Get configuration variables
        $variables = $this->configuration ?? [];

        // Use the Liquid template engine to resolve variables
        $environment = App::make('liquid.environment');
        $liquidTemplate = $environment->parseString($template);
        $context = $environment->newRenderContext(data: $variables);

        return $liquidTemplate->render($context);
    }

    /**
     * Render the plugin's markup
     *
     * @throws LiquidException
     */
    public function render(string $size = 'full', bool $standalone = true): string
    {
        if ($this->render_markup) {
            $renderedContent = '';

            if ($this->markup_language === 'liquid') {
                $environment = App::make('liquid.environment');

                // Register all custom filters
                $environment->filterRegistry->register(Numbers::class);
                $environment->filterRegistry->register(Data::class);
                $environment->filterRegistry->register(StringMarkup::class);
                $environment->filterRegistry->register(Uniqueness::class);
                $environment->filterRegistry->register(Localization::class);

                $template = $environment->parseString($this->render_markup);
                $context = $environment->newRenderContext(data: [
                    'size' => $size,
                    'data' => $this->data_payload,
                    'config' => $this->configuration ?? [],
                    ...(is_array($this->data_payload) ? $this->data_payload : [])
                ]);
                $renderedContent = $template->render($context);
            } else {
                $renderedContent = Blade::render($this->render_markup, [
                    'size' => $size,
                    'data' => $this->data_payload,
                    'config' => $this->configuration ?? []
                ]);
            }

            if ($standalone) {
                return view('trmnl-layouts.single', [
                    'slot' => $renderedContent,
                ])->render();
            }

            return $renderedContent;
        }

        if ($this->render_markup_view) {
            if ($standalone) {
                return view('trmnl-layouts.single', [
                    'slot' => view($this->render_markup_view, [
                        'size' => $size,
                        'data' => $this->data_payload,
                        'config' => $this->configuration ?? []
                    ])->render(),
                ])->render();
            }

            return view($this->render_markup_view, [
                'size' => $size,
                'data' => $this->data_payload,
                'config' => $this->configuration ?? []
            ])->render();

        }

        return '<p>No render markup yet defined for this plugin.</p>';
    }

    /**
     * Get a configuration value by key
     */
    public function getConfiguration(string $key, $default = null)
    {
        return $this->configuration[$key] ?? $default;
    }
}
