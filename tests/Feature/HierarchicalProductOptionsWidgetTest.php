<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Widgets\HierarchicalProductOptionsWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class HierarchicalProductOptionsWidgetTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Task 5.1 — buildHierarchicalPermutations: only selected combos generated
    // -------------------------------------------------------------------------

    #[Test]
    public function it_generates_only_selected_parent_child_combinations(): void
    {
        $widget = $this->makeWidget();

        $parentEntry = [
            'value' => 'Tipo de Lente',
            'option_values' => [
                ['value' => 'Monofocal', 'enabled' => true],
                ['value' => 'Bifocal', 'enabled' => true],
            ],
        ];

        $childEntry = [
            'value' => 'Graduación',
            'parent_value_selections' => [
                'Monofocal' => ['Baja', 'Alta'],
                'Bifocal'   => ['Baja'],
            ],
            'option_values' => [],
        ];

        $result = $this->callPrivate($widget, 'buildHierarchicalPermutations', $parentEntry, $childEntry);

        $this->assertCount(3, $result);
        $this->assertContains(['Tipo de Lente' => 'Monofocal', 'Graduación' => 'Baja'], $result);
        $this->assertContains(['Tipo de Lente' => 'Monofocal', 'Graduación' => 'Alta'], $result);
        $this->assertContains(['Tipo de Lente' => 'Bifocal', 'Graduación' => 'Baja'], $result);
        $this->assertNotContains(['Tipo de Lente' => 'Bifocal', 'Graduación' => 'Alta'], $result);
        $this->assertNotContains(['Tipo de Lente' => 'Bifocal', 'Graduación' => 'Muy Alta'], $result);
    }

    #[Test]
    public function it_excludes_disabled_parent_values_from_permutations(): void
    {
        $widget = $this->makeWidget();

        $parentEntry = [
            'value' => 'Tipo de Lente',
            'option_values' => [
                ['value' => 'Monofocal', 'enabled' => true],
                ['value' => 'Bifocal', 'enabled' => false],
            ],
        ];

        $childEntry = [
            'value' => 'Graduación',
            'parent_value_selections' => [
                'Monofocal' => ['Baja', 'Alta'],
                'Bifocal'   => ['Baja'],
            ],
            'option_values' => [],
        ];

        $result = $this->callPrivate($widget, 'buildHierarchicalPermutations', $parentEntry, $childEntry);

        $this->assertCount(2, $result);
        $this->assertNotContains(['Tipo de Lente' => 'Bifocal', 'Graduación' => 'Baja'], $result);
    }

    // -------------------------------------------------------------------------
    // Task 5.2 — hierarchical + independent options mixed (cartesian)
    // -------------------------------------------------------------------------

    #[Test]
    public function it_combines_hierarchical_permutations_with_independent_options(): void
    {
        $widget = $this->makeWidget();

        $hierarchicalPerms = [
            ['Tipo de Lente' => 'Monofocal', 'Graduación' => 'Baja'],
            ['Tipo de Lente' => 'Monofocal', 'Graduación' => 'Alta'],
        ];

        $independentOptions = ['Color' => ['Blanco', 'Negro']];

        $result = $this->callPrivate($widget, 'combineWithIndependent', $hierarchicalPerms, $independentOptions);

        $this->assertCount(4, $result);
        $this->assertContains([
            'Tipo de Lente' => 'Monofocal',
            'Graduación' => 'Baja',
            'Color' => 'Blanco',
        ], $result);
        $this->assertContains([
            'Tipo de Lente' => 'Monofocal',
            'Graduación' => 'Alta',
            'Color' => 'Negro',
        ], $result);
    }

    // -------------------------------------------------------------------------
    // Task 5.3 — inferParentValueSelections from existing variants
    //   Requires Lunar model factories — skipped until factories are available
    // -------------------------------------------------------------------------

    #[Test]
    public function it_infers_parent_value_selections_from_existing_variants(): void
    {
        $this->markTestSkipped(
            'Requires Lunar model factories for ProductOption/Variant — implement once available.'
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeWidget(): HierarchicalProductOptionsWidget
    {
        return new HierarchicalProductOptionsWidget();
    }

    private function callPrivate(object $object, string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod($object, $method);
        $ref->setAccessible(true);

        return $ref->invoke($object, ...$args);
    }
}
