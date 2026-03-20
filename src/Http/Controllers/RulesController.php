<?php

namespace KeyAgency\KaiPersonalize\Http\Controllers;

use Illuminate\Http\Request;
use KeyAgency\KaiPersonalize\Edition;
use KeyAgency\KaiPersonalize\Models\Rule;
use Statamic\Http\Controllers\CP\CpController;

class RulesController extends CpController
{
    public function index()
    {
        $rules = Rule::orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('kai-personalize::rules.index', [
            'title' => __('kai-personalize::messages.rules.title'),
            'rules' => $rules,
        ]);
    }

    public function create()
    {
        return view('kai-personalize::rules.create', [
            'title' => __('kai-personalize::messages.rules.create'),
            'rule' => null,
        ]);
    }

    public function store(Request $request)
    {
        // Check rule limit for free edition
        if (Edition::isFree() && $request->has('is_active')) {
            $maxRules = Edition::getLimit('max_rules');
            $activeRuleCount = Rule::where('is_active', true)->count();

            if ($activeRuleCount >= $maxRules) {
                return back()
                    ->withInput()
                    ->with('error', __('kai-personalize::messages.rules.limit_reached', [
                        'max' => $maxRules,
                    ]));
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'conditions' => 'required|json',
            'priority' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Decode conditions JSON
        $validated['conditions'] = json_decode($validated['conditions'], true);
        $validated['is_active'] = $request->has('is_active');

        $rule = Rule::create($validated);

        return redirect()
            ->route('statamic.cp.kai-personalize.rules.index')
            ->with('success', __('kai-personalize::messages.rules.created'));
    }

    public function show($id)
    {
        $rule = Rule::findOrFail($id);

        $stats = [
            'total_matches' => $rule->logs()->count(),
            'matches_today' => $rule->logs()
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
            'last_matched' => $rule->logs()
                ->latest('created_at')
                ->first()
                ?->created_at
                ?->diffForHumans(),
        ];

        return view('kai-personalize::rules.show', [
            'title' => $rule->name,
            'rule' => $rule,
            'stats' => $stats,
        ]);
    }

    public function edit($id)
    {
        $rule = Rule::findOrFail($id);

        return view('kai-personalize::rules.edit', [
            'title' => __('kai-personalize::messages.rules.edit'),
            'rule' => $rule,
        ]);
    }

    public function update(Request $request, $id)
    {
        $rule = Rule::findOrFail($id);

        // Check rule limit for free edition when activating a rule
        if (Edition::isFree() && $request->has('is_active') && !$rule->is_active) {
            $maxRules = Edition::getLimit('max_rules');
            $activeRuleCount = Rule::where('is_active', true)->where('id', '!=', $id)->count();

            if ($activeRuleCount >= $maxRules) {
                return back()
                    ->withInput()
                    ->with('error', __('kai-personalize::messages.rules.limit_reached', [
                        'max' => $maxRules,
                    ]));
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'conditions' => 'required|json',
            'priority' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Decode conditions JSON
        $validated['conditions'] = json_decode($validated['conditions'], true);
        $validated['is_active'] = $request->has('is_active');

        $rule->update($validated);

        return redirect()
            ->route('statamic.cp.kai-personalize.rules.show', $rule->id)
            ->with('success', __('kai-personalize::messages.rules.updated'));
    }

    public function destroy($id)
    {
        $rule = Rule::findOrFail($id);
        $rule->delete();

        return redirect()
            ->route('statamic.cp.kai-personalize.rules.index')
            ->with('success', __('kai-personalize::messages.rules.deleted'));
    }
}
