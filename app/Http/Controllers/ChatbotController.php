<?php

namespace App\Http\Controllers;

use App\Services\AI\AiAgentService;
use App\Services\ChatbotService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __construct(
        protected ChatbotService $chatbotService,
        protected AiAgentService $agentService,
    ) {
    }

    public function query(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:2000',
            'agent_mode' => 'nullable|string|in:war_general,progression_coach,base_architect,farming_master,supercell_pro',
            'agent_actions' => 'nullable|boolean',
        ]);

        $user = auth()->user();
        $agentMode = $validated['agent_mode'] ?? 'war_general';
        $enableActions = filter_var($validated['agent_actions'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if ($enableActions) {
            $result = $this->agentService->handle($user, $validated['question'], $agentMode);

            return response()->json($result);
        }

        $response = $this->chatbotService->answerUserQuestionWithAgent(
            $user,
            $validated['question'],
            $agentMode
        );

        return response()->json([
            'ok' => true,
            'agent_mode' => $agentMode,
            'action' => 'chat',
            'answer' => $response,
        ]);
    }
}
