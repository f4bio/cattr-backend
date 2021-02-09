<?php


namespace App\Http\Controllers\Api\Google;

use App\Exceptions\ExternalServices\Google\ActionDeterminationException;
use App\Helpers\ExternalServices\Google\ActionDeterminator;
use App\Http\Controllers\Controller;
use Google_Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class OAuthController extends Controller
{
    public function authInit(
        Request $request,
        Google_Client $googleClient,
        ActionDeterminator $actionDeterminator
    ): JsonResponse {
        try {
            $action = $actionDeterminator->determinate((string)$request->get('action_id'));
        } catch (ActionDeterminationException $actionDeterminationException) {
            Log::error($actionDeterminationException->getMessage());

            return new JsonResponse([
                'message' => $actionDeterminationException->getMessage(),
                'errors' => []
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $googleClient->setRedirectUri($action->getRedirectUrl());
            $response = $googleClient->createAuthUrl($action->getScopes());

            return new JsonResponse(['url' => $response]);
        } catch (Throwable $exception) {
            Log::alert(sprintf("%s%s%s", $exception->getMessage(), PHP_EOL, $exception->getTraceAsString()));

            return new JsonResponse(
                ['message' => 'Authorization via Google was failed', 'errors' => [],],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
