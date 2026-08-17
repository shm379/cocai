<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __construct(protected ChatbotService $chatbotService)
    {
    }

    public function query(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:2000',
            'agent_mode' => 'nullable|string|in:war_general,progression_coach,base_architect,farming_master,supercell_pro',
        ]);

        $user = auth()->user();
        $agentMode = $validated['agent_mode'] ?? 'war_general';

        $response = $this->chatbotService->answerUserQuestionWithAgent(
            $user,
            $validated['question'],
            $agentMode
        );

        return response()->json([
            'ok' => true,
            'agent_mode' => $agentMode,
            'answer' => $response,
        ]);
    }
}
