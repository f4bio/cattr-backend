<?php


namespace App\Http\Controllers\Api\Statistic;

use App\Exceptions\ExternalServices\Google\Sheets\ExportException;
use App\Http\Controllers\Controller;
use App\Services\External\Google\SheetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    private SheetsService $sheetsService;

    public function __construct(SheetsService $sheetsService)
    {
        parent::__construct();
        $this->sheetsService = $sheetsService;
    }

    public function exportInGoogleSheets(Request $request): JsonResponse
    {
        $code = (string)$request->query->get('code');
        $state = (string)$request->query->get('state');

        if (!($code && $state)) {
            Log::error('Request does not contains params CODE and STATE [handle redirect after Google OAuth: export in Google Sheets]');

            return new JsonResponse(
                ['messages' => 'Failed export in Google Sheets', 'errors' => []],
                Response::HTTP_PRECONDITION_FAILED
            );
        }

        try {
            $linkToCreatedSheet = $this->sheetsService->exportDashboardReport($code, $state);
        } catch (ExportException $e) {
            Log::error($e->getMessage());

            return new JsonResponse(
                [
                    'message' => $e->getMessage(),
                    'errors' => $e->getInvalidParams()
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return new JsonResponse(['url' => $linkToCreatedSheet,]);
    }
}
