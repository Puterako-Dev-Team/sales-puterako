<?php

namespace App\Http\Controllers;

use App\Models\SurveyColumnFormula;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SurveyFormulaController extends Controller
{
    /**
     * Display formula management page.
     */
    public function index(): View
    {
        $formulas = SurveyColumnFormula::ordered()->get();
        return view('survey-formulas.index', compact('formulas'));
    }

    /**
     * Get all active formulas as JSON (for frontend).
     */
    public function getFormulas(): JsonResponse
    {
        $formulas = SurveyColumnFormula::getFormulaConfig();
        return response()->json([
            'success' => true,
            'formulas' => $formulas,
        ]);
    }

    /**
     * Store a new formula.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'column_key' => 'required|string|max:100|unique:survey_column_formulas,column_key',
            'formula' => 'required|string|max:500',
            'description' => 'nullable|string|max:255',
            'group_name' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        $formula = new SurveyColumnFormula($validated);
        
        // Validate formula syntax
        $validation = $formula->validateFormula();
        if ($validation !== true) {
            return response()->json([
                'success' => false,
                'message' => $validation,
            ], 422);
        }

        $formula->save();

        return response()->json([
            'success' => true,
            'message' => 'Formula berhasil ditambahkan',
            'formula' => $formula,
        ]);
    }

    /**
     * Update an existing formula.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $formula = SurveyColumnFormula::findOrFail($id);

        $validated = $request->validate([
            'column_key' => 'required|string|max:100|unique:survey_column_formulas,column_key,' . $id,
            'formula' => 'required|string|max:500',
            'description' => 'nullable|string|max:255',
            'group_name' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        $formula->fill($validated);
        
        // Validate formula syntax
        $validation = $formula->validateFormula();
        if ($validation !== true) {
            return response()->json([
                'success' => false,
                'message' => $validation,
            ], 422);
        }

        $formula->save();

        return response()->json([
            'success' => true,
            'message' => 'Formula berhasil diperbarui',
            'formula' => $formula,
        ]);
    }

    /**
     * Delete a formula.
     */
    public function destroy(int $id): JsonResponse
    {
        $formula = SurveyColumnFormula::findOrFail($id);
        $formula->delete();

        return response()->json([
            'success' => true,
            'message' => 'Formula berhasil dihapus',
        ]);
    }

    /**
     * Toggle formula active status.
     */
    public function toggleActive(int $id): JsonResponse
    {
        $formula = SurveyColumnFormula::findOrFail($id);
        $formula->is_active = !$formula->is_active;
        $formula->save();

        return response()->json([
            'success' => true,
            'message' => $formula->is_active ? 'Formula diaktifkan' : 'Formula dinonaktifkan',
            'is_active' => $formula->is_active,
        ]);
    }

    /**
     * Reorder formulas.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:survey_column_formulas,id',
        ]);

        foreach ($validated['order'] as $index => $id) {
            SurveyColumnFormula::where('id', $id)->update(['order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Urutan formula berhasil diperbarui',
        ]);
    }

    /**
     * Seed default formulas.
     */
    public function seedDefaults(): JsonResponse
    {
        $defaults = SurveyColumnFormula::getDefaultFormulas();
        $created = 0;
        $skipped = 0;

        foreach ($defaults as $formulaData) {
            $exists = SurveyColumnFormula::where('column_key', $formulaData['column_key'])->exists();
            if (!$exists) {
                SurveyColumnFormula::create($formulaData);
                $created++;
            } else {
                $skipped++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil menambahkan {$created} formula default. {$skipped} formula sudah ada.",
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Get formula by column key.
     */
    public function getByColumnKey(string $columnKey): JsonResponse
    {
        $formula = SurveyColumnFormula::where('column_key', $columnKey)->first();

        if (!$formula) {
            return response()->json([
                'success' => false,
                'message' => 'Formula tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'formula' => $formula,
        ]);
    }

    /**
     * Test a formula expression.
     */
    public function testFormula(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'formula' => 'required|string|max:500',
            'test_values' => 'required|array',
        ]);

        $formula = new SurveyColumnFormula(['formula' => $validated['formula']]);
        
        // Validate syntax
        $validation = $formula->validateFormula();
        if ($validation !== true) {
            return response()->json([
                'success' => false,
                'message' => $validation,
            ], 422);
        }

        // Calculate result using test values
        try {
            $expression = $validated['formula'];
            foreach ($validated['test_values'] as $key => $value) {
                $expression = preg_replace('/\b' . preg_quote($key, '/') . '\b/', (string) $value, $expression);
            }

            // Safe evaluation using basic math
            // Check if expression is safe (only contains numbers, operators, parentheses)
            if (!preg_match('/^[\d\s\+\-\*\/\(\)\.]+$/', $expression)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formula contains unsupported characters after substitution',
                ], 422);
            }

            // Evaluate using JavaScript-style eval alternative
            $result = eval("return $expression;");

            return response()->json([
                'success' => true,
                'expression' => $expression,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error evaluating formula: ' . $e->getMessage(),
            ], 422);
        }
    }
}
