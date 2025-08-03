<?php

declare(strict_types=1);

namespace Tests\Unit\Liquid;

use App\Liquid\FileSystems\InlineTemplatesFileSystem;
use App\Liquid\Tags\TemplateTag;
use Keepsuit\Liquid\Environment;
use Keepsuit\Liquid\Exceptions\LiquidException;
use Keepsuit\Liquid\Tags\RenderTag;
use PHPUnit\Framework\TestCase;

class InlineTemplatesTest extends TestCase
{
    protected Environment $environment;
    protected InlineTemplatesFileSystem $fileSystem;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fileSystem = new InlineTemplatesFileSystem();
        $this->environment = new Environment(
            fileSystem: $this->fileSystem
        );
        $this->environment->tagRegistry->register(TemplateTag::class);
        $this->environment->tagRegistry->register(RenderTag::class);
    }

    public function test_template_tag_registers_template(): void
    {
        $template = $this->environment->parseString(<<<'LIQUID'
{% template session %}
<div class="layout">
  <div class="columns">
    <div class="column">
      <div class="markdown gap--large">
        <div class="value{{ size_mod }} text--center">
          {{ facts[randomNumber] }}
        </div>
      </div>
    </div>
  </div>
</div>
{% endtemplate %}
LIQUID
        );

        $context = $this->environment->newRenderContext(
            data: [
                'facts' => ['Fact 1', 'Fact 2', 'Fact 3'],
                'randomNumber' => 1,
                'size_mod' => '--large',
            ]
        );

        $result = $template->render($context);

        // Template tag should not output anything
        $this->assertEquals('', $result);

        // Template should be registered in the file system
        $this->assertTrue($this->fileSystem->hasTemplate('session'));
        
        $registeredTemplate = $this->fileSystem->readTemplateFile('session');
        $this->assertStringContainsString('{{ facts[randomNumber] }}', $registeredTemplate);
        $this->assertStringContainsString('{{ size_mod }}', $registeredTemplate);
    }

    public function test_template_tag_with_render_tag(): void
    {
        $template = $this->environment->parseString(<<<'LIQUID'
{% template session %}
<div class="layout">
  <div class="columns">
    <div class="column">
      <div class="markdown gap--large">
        <div class="value{{ size_mod }} text--center">
          {{ facts[randomNumber] }}
        </div>
      </div>
    </div>
  </div>
</div>
{% endtemplate %}

{% render "session",
  trmnl: trmnl,
  facts: facts,
  randomNumber: randomNumber,
  size_mod: ""
%}
LIQUID
        );

        $context = $this->environment->newRenderContext(
            data: [
                'facts' => ['Fact 1', 'Fact 2', 'Fact 3'],
                'randomNumber' => 1,
                'trmnl' => ['plugin_settings' => ['instance_name' => 'Test']],
            ]
        );

        $result = $template->render($context);

        // Should render the template content
        $this->assertStringContainsString('Fact 2', $result); // facts[1]
        $this->assertStringContainsString('class="layout"', $result);
        $this->assertStringContainsString('class="value text--center"', $result);
    }

    public function test_template_tag_with_multiple_templates(): void
    {
        $template = $this->environment->parseString(<<<'LIQUID'
{% template session %}
<div class="layout">
  <div class="columns">
    <div class="column">
      <div class="markdown gap--large">
        <div class="value{{ size_mod }} text--center">
          {{ facts[randomNumber] }}
        </div>
      </div>
    </div>
  </div>
</div>
{% endtemplate %}

{% template title_bar %}
<div class="title_bar">
  <img class="image" src="https://res.jwq.lol/img/lumon.svg">
  <span class="title">{{ trmnl.plugin_settings.instance_name }}</span>
  <span class="instance">{{ instance }}</span>
</div>
{% endtemplate %}

<div class="view view--{{ size }}">
{% render "session",
  trmnl: trmnl,
  facts: facts,
  randomNumber: randomNumber,
  size_mod: ""
%}

{% render "title_bar",
  trmnl: trmnl,
  instance: "Please try to enjoy each fact equally."
%}
</div>
LIQUID
        );

        $context = $this->environment->newRenderContext(
            data: [
                'size' => 'full',
                'facts' => ['Fact 1', 'Fact 2', 'Fact 3'],
                'randomNumber' => 1,
                'trmnl' => ['plugin_settings' => ['instance_name' => 'Test Plugin']],
            ]
        );

        $result = $template->render($context);

        // Should render both templates
        $this->assertStringContainsString('Fact 2', $result);
        $this->assertStringContainsString('Test Plugin', $result);
        $this->assertStringContainsString('Please try to enjoy each fact equally', $result);
        $this->assertStringContainsString('class="view view--full"', $result);
    }

    public function test_template_tag_invalid_name(): void
    {
        $this->expectException(LiquidException::class);

        $template = $this->environment->parseString(<<<'LIQUID'
{% template invalid-name %}
<div>Content</div>
{% endtemplate %}
LIQUID
        );

        $context = $this->environment->newRenderContext();

        $template->render($context);
    }

    public function test_template_tag_without_file_system(): void
    {
        $template = $this->environment->parseString(<<<'LIQUID'
{% template session %}
<div>Content</div>
{% endtemplate %}
LIQUID
        );

        $context = $this->environment->newRenderContext();

        $result = $template->render($context);

        // Should not throw an error and should return empty string
        $this->assertEquals('', $result);
    }
} 