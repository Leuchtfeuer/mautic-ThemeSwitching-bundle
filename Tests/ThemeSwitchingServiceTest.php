<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Tests\Service;


use MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Service\ThemeSwitchingService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Psr\Container\ContainerInterface;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Doctrine\ORM\EntityManagerInterface;

use Twig\Loader\ArrayLoader;
use Twig\Environment;


class ThemeSwitchingServiceTest extends TestCase
{
    private ThemeSwitchingService $service;



    protected function setUp(): void
    {
        $logger = new NullLogger();
        $paramsHelper = $this->createMock(CoreParametersHelper::class);
        $em = $this->createMock(EntityManagerInterface::class);

        // Create a real Twig environment
        $twig = new Environment(new ArrayLoader());

        // Minimal no-op container to satisfy the new constructor signature
        $container = new class implements ContainerInterface {
            public function get(string $id)
            {
                // If this ever runs during these tests, surface immediately.
                throw new \RuntimeException("Unexpected container->get('$id') in ThemeSwitchingServiceTest.");
            }
            public function has(string $id): bool
            {
                return false;
            }
        };

        $this->service = new ThemeSwitchingService(
            $logger,
            $paramsHelper,
            $em,
            $twig,
            $container,
            null
        );
    }



    /**
     * @dataProvider mergeProvider
     */
    public function testMergeMjmlTemplates(string $old, string $new, bool $translationMode, string $expectedContains): void
    {
        $result = $this->service->mergeMjml($old, $new, $translationMode);
        $this->assertStringContainsString($expectedContains, $result);
    }


    public static function mergeProvider(): array
    {
        return [
            'translation mode basic' => [
                <<<HTML
<mjml>
  <mj-head></mj-head>
  <mj-body>
    <!-- LOCKED_START --><mj-section><mj-text>Old Header</mj-text></mj-section><!-- LOCKED_END -->
    <mj-section><mj-text>Keep this content</mj-text></mj-section>
    <!-- LOCKED_START --><mj-section><mj-text>Old Footer</mj-text></mj-section><!-- LOCKED_END -->
  </mj-body>
</mjml>
HTML,
                <<<HTML
<mjml>
  <mj-head><mj-title>New Head</mj-title></mj-head>
  <mj-body>
    <!-- LOCKED_START --><mj-section><mj-text>New Header</mj-text></mj-section><!-- LOCKED_END -->
    <mj-section><mj-text>Extra Block</mj-text></mj-section>
    <!-- LOCKED_START --><mj-section><mj-text>New Footer</mj-text></mj-section><!-- LOCKED_END -->
  </mj-body>
</mjml>
HTML,
                true,
                '<mj-text>Keep this content</mj-text>'
            ],

            'non-translation mode adds unlocked' => [
                <<<HTML
<mjml><mj-head></mj-head><mj-body>
    <mj-section><mj-text>Content A</mj-text></mj-section>
</mj-body></mjml>
HTML,
                <<<HTML
<mjml><mj-head></mj-head><mj-body>
    <mj-section><mj-text>From Theme</mj-text></mj-section>
</mj-body></mjml>
HTML,
                false,
                '<mj-text>From Theme</mj-text>'
            ],

            'more locked in old than new' => [
                <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>L1</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>A1</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>L2</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>A2</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>L3</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML,
                <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>LT1</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>B1</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>LT2</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML,
                false,
                '<mj-text>LT2</mj-text>'
            ],

            'more locked in new than old' => [
                <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>L1</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>A1</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>L2</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML,
                <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>LT1</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>B1</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>LT2</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>B2</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>LT3</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML,
                false,
                '<mj-text>LT3</mj-text>'
            ],

            'multiple locked in a row' => [
                <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>L1</mj-text></mj-section><!-- LOCKED_END -->
  <!-- LOCKED_START --><mj-section><mj-text>L2</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>A1</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>L3</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML,
                <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>LT1</mj-text></mj-section><!-- LOCKED_END -->
  <!-- LOCKED_START --><mj-section><mj-text>LT2</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML,
                true,
                '<mj-text>A1</mj-text>'
            ],

            'no locked in old' => [
                <<<HTML
<mjml><mj-body>
  <mj-section><mj-text>A1</mj-text></mj-section>
</mj-body></mjml>
HTML,
                <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>LT1</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>B1</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>LT2</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML,
                false,
                '<mj-text>LT1</mj-text>'
            ],

            'no locked in new' => [
                <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>L1</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>A1</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>L2</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML,
                <<<HTML
<mjml><mj-body>
  <mj-section><mj-text>B1</mj-text></mj-section>
</mj-body></mjml>
HTML,
                true,
                '<mj-text>A1</mj-text>'
            ],

        'empty old and new' => [
        '', '', false, '<mjml>'
    ],

            'missing mj-head in new' => [
        <<<HTML
<mjml><mj-head></mj-head><mj-body><mj-section><mj-text>Keep</mj-text></mj-section></mj-body></mjml>
HTML,
        <<<HTML
<mjml><mj-body><mj-section><mj-text>New</mj-text></mj-section></mj-body></mjml>
HTML,
        false,
        '<mj-head></mj-head>'
    ],

            'malformed LOCKED section' => [
        <<<HTML
<mjml><mj-body><!-- LOCKED_START --><mj-section><mj-text>Oops</mj-text></mj-section></mj-body></mjml>
HTML,
        <<<HTML
<mjml><mj-body><mj-section><mj-text>Theme</mj-text></mj-section></mj-body></mjml>
HTML,
        true,
        '<mj-text>Oops</mj-text>'
    ],

            'translation mode skips new unlocked' => [
        <<<HTML
<mjml><mj-body><mj-section><mj-text>Content</mj-text></mj-section></mj-body></mjml>
HTML,
        <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>LT1</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>New Unlocked</mj-text></mj-section>
</mj-body></mjml>
HTML,
        true,
        '<mj-text>Content</mj-text>'
    ],

            'whitespace robustness in markers' => [
        <<<HTML
<mjml><mj-body>
<!--LOCKED_START--> <mj-section><mj-text>L1</mj-text></mj-section> <!--LOCKED_END-->
<mj-section><mj-text>Content</mj-text></mj-section>
</mj-body></mjml>
HTML,
        <<<HTML
<mjml><mj-body>
<!-- LOCKED_START --><mj-section><mj-text>LT1</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML,
        false,
        '<mj-text>LT1</mj-text>'
    ],

            '20+ locked and unlocked segments stress test' => [
        self::generateManySegments(20, false),
        self::generateManySegments(25, true),
        false,
        '<mj-text>LT25</mj-text>'
    ],
        ];


    }




    private static function generateManySegments(int $count, bool $isTheme): string
    {
        $segments = [];
        $prefix = $isTheme ? 'LT' : 'L';

        for ($i = 1; $i <= $count; $i++) {
            $segments[] = <<<HTML
<!-- LOCKED_START --><mj-section><mj-text>{$prefix}{$i}</mj-text></mj-section><!-- LOCKED_END -->
<mj-section><mj-text>A{$i}</mj-text></mj-section>
HTML;
        }

        $bodyContent = implode("\n", $segments);

        return <<<HTML
<mjml><mj-body>
$bodyContent
</mj-body></mjml>
HTML;
    }


    public function testExtraLockedBlocksAreDropped()
    {
        $old = <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>L1</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>A1</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>L2</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>A2</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>L3</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML;

        $new = <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>LT1</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>B1</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>LT2</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML;

        $result = $this->service->mergeMjml($old, $new, false);

        // Should contain correct theme locked and unlocked blocks
        $this->assertStringContainsString('<mj-text>LT1</mj-text>', $result);
        $this->assertStringContainsString('<mj-text>LT2</mj-text>', $result);
        $this->assertStringContainsString('<mj-text>A1</mj-text>', $result);
        $this->assertStringContainsString('<mj-text>A2</mj-text>', $result);

        // Should NOT contain extra locked block from old
        $this->assertStringNotContainsString('<mj-text>L3</mj-text>', $result, 'Extra locked block from old email was not dropped!');
    }


    public function testExtraLockedBlocksFromNewThemeAreAppended()
    {
        $old = <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>O1</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>Old Content</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>O2</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML;

        $new = <<<HTML
<mjml><mj-body>
  <!-- LOCKED_START --><mj-section><mj-text>N1</mj-text></mj-section><!-- LOCKED_END -->
  <mj-section><mj-text>New Theme Unlocked</mj-text></mj-section>
  <!-- LOCKED_START --><mj-section><mj-text>N2</mj-text></mj-section><!-- LOCKED_END -->
  <!-- LOCKED_START --><mj-section><mj-text>N3</mj-text></mj-section><!-- LOCKED_END -->
  <!-- LOCKED_START --><mj-section><mj-text>N4</mj-text></mj-section><!-- LOCKED_END -->
</mj-body></mjml>
HTML;

        $result = $this->service->mergeMjml($old, $new, false);

        // Should contain all new theme LOCKEDs (including N3 and N4 at the end)
        $this->assertStringContainsString('<mj-text>N1</mj-text>', $result);
        $this->assertStringContainsString('<mj-text>N2</mj-text>', $result);
        $this->assertStringContainsString('<mj-text>N3</mj-text>', $result);
        $this->assertStringContainsString('<mj-text>N4</mj-text>', $result);

        // Should contain unlocked content from old
        $this->assertStringContainsString('<mj-text>Old Content</mj-text>', $result);

        // Should NOT contain old LOCKEDs
        $this->assertStringNotContainsString('<mj-text>O1</mj-text>', $result);
        $this->assertStringNotContainsString('<mj-text>O2</mj-text>', $result);
    }

    public function testLoadThemeMjmlRejectsPathTraversal(): void
    {
        $themesPath = realpath(__DIR__.'/../../../themes');
        $this->assertNotFalse($themesPath);

        $paramsHelper = $this->createMock(CoreParametersHelper::class);
        $paramsHelper->method('get')->willReturnCallback(static function (string $key) use ($themesPath) {
            return 'themes_path' === $key ? $themesPath : null;
        });

        $service = new ThemeSwitchingService(
            new NullLogger(),
            $paramsHelper,
            $this->createMock(EntityManagerInterface::class),
            new Environment(new ArrayLoader()),
            new class implements ContainerInterface {
                public function get(string $id)
                {
                    throw new \RuntimeException("Unexpected container->get('$id')");
                }

                public function has(string $id): bool
                {
                    return false;
                }
            },
            null
        );

        $method = new \ReflectionMethod(ThemeSwitchingService::class, 'loadThemeMjml');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $method->invoke($service, '../../config/local.php');
    }


}
