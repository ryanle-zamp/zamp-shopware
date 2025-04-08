<?php

namespace ZampTax\Core\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route(defaults: ['_routeScope' => ['api']])]
class LogController extends AbstractController
{
    private $projectDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->projectDir = $params->get('kernel.project_dir');
    }

    #[Route('/api/v1/_action/zamp-tax/logs', name: 'api.zamp_tax.logs', methods: ["GET"])]
    public function getLog(Request $request): JsonResponse
    {
        $date = $request->query->get('date');

        if (!$date) {
            return new JsonResponse(['error' => 'Date parameter missing.'], 400);
        }

        $logPath = $this->projectDir . '/var/log/ZampTax-' . $date . '.log';

        if (!file_exists($logPath)) {
            return new JsonResponse(['error' => 'Log file not found.', 'path' => $logPath], 404);
        }

        try {
            $logContent = file_get_contents($logPath);
            if ($logContent === false) {
                throw new \RuntimeException("Could not read log file at $logPath");
            }

            // Process each line
            $lines = explode("\n", $logContent);
            $formattedLines = [];

            foreach ($lines as $line) {
                $trimmed = trim($line);

                // Try to extract any JSON object inside the line
                if (preg_match('/\{.*\}/', $trimmed, $matches)) {
                    $jsonString = $matches[0];

                    $decoded = json_decode($jsonString, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $prettyJson = json_encode($decoded, JSON_PRETTY_PRINT);
                        $formattedLine = str_replace($jsonString, $prettyJson, $trimmed);
                        $formattedLines[] = $formattedLine;
                        continue;
                    }
                }

                // If not JSON or fails to decode, just add the raw line
                $formattedLines[] = $trimmed;
            }

            $finalLog = implode("\n\n", $formattedLines);

            return new JsonResponse([
                'log' => '<pre>' . htmlspecialchars($finalLog) . '</pre>'
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Server error reading log file.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}