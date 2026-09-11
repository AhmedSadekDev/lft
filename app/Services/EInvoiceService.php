<?php
namespace App\Services;

use App\Traits\ResponseTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class EInvoiceService
{
    use ResponseTrait;

    protected $client;
    protected $baseUri;

    public function __construct()
    {
        $this->client  = new Client();
        $this->baseUri = env('API_BASE_URL');
    }

    /**
     * Get Access Token
     */
    public function getAccessToken($clientId, $clientSecret)
    {
        try {
            $response = $this->client->post("https://id.eta.gov.eg/connect/token", [
                'form_params' => [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'scope'         => 'InvoicingAPI',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return $data['access_token'] ?? null;
        } catch (GuzzleException $e) {
            return null;
        }
    }

    /**
     * Submit Invoice (V1.0)
     */
    public function submitInvoice($invoiceData, $accessToken)
    {
        try {
            $response = $this->client->post("{$this->baseUri}/api/v1.0/documentsubmissions", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => json_encode([
                    'documents' =>  [$invoiceData],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]);

            return [
                'status'  => true,
                'data'    => json_decode($response->getBody(), true),
                'message' => 'Invoice submitted successfully',
            ];

        } catch (GuzzleHttp\Exception\ClientException $e) {
            $responseBody = json_decode($e->getResponse()->getBody()->getContents(), true);

            if (isset($responseBody['error']) && str_contains($responseBody['error'], 'identical to a previous payload')) {

                preg_match('/Try to submit payload after (\d+) se/', $responseBody['error'], $matches);

                if (! empty($matches[1])) {
                    $seconds          = (int) $matches[1];
                    $minutes          = floor($seconds / 60);
                    $remainingSeconds = $seconds % 60;

                    $formattedTime = $minutes > 0
                    ? "{$minutes} دقيقة و {$remainingSeconds} ثانية"
                    : "{$remainingSeconds} ثانية";
                } else {
                    $formattedTime = "بعض الوقت";
                }

                return [
                    'status'  => false,
                    'message' => "تم إرسال نفس بيانات الفاتورة مؤخرًا، يرجى الانتظار {$formattedTime} قبل إعادة الإرسال.",
                ];
            }

            return [
                'status'  => false,
                'message' => 'ETA API Error: ' . json_encode($responseBody),
            ];
        }

    }
    /**
     * Submit Invoice (V1.0)
     */
    public function getDocumentShareToken(string $documentUuid, string $accessToken)
    {
        try {
            $response = $this->client->get("{$this->baseUri}/api/v1/documents/$documentUuid/raw", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ]
            ]);
    
            $responseBody = json_decode($response->getBody(), true);
            return [
                'status'  => true,
                'data'    => [
                    'share_url' => $responseBody['shareUrl'] ?? null,
                    'token'     => $this->extractTokenFromUrl($responseBody['shareUrl'] ?? ''),
                ],
                'message' => 'Share token retrieved successfully',
            ];
        } catch (\Exception $e) {
            return [
                'status'  => false,
                'message' => 'Failed to retrieve share token: ' . $e->getMessage(),
            ];
        }
    }


    /**
     * Cancel or Reject Invoice (V1.0)
     */
    public function updateInvoiceState($invoice_uuid, $status, $reason, $accessToken)
    {
        try {
            $response = $this->client->put("{$this->baseUri}/api/v1.0/documents/state/$invoice_uuid/state", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type'  => 'application/json',
                ],
                'body'    => json_encode( [
                    'status' => $status,
                    'reason' => $reason,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            
            $res = $e->hasResponse() ?   json_decode($e->getResponse()->getBody()->getContents(), true)  : [];
            
            return [
                'status'  => false,
                'message' =>$e->hasResponse() ? 'تم تغيير الحالة بواسطة المستقبل او الطرف الثالت' :  $e->getMessage(),
                'orgRes' =>  $res
            ];
        }
    }

    /**
     * Get Invoice (V1.0)
     */
    public function getInvoice($invoice_uuid, $accessToken)
    {
        try {
            $response = $this->client->get("{$this->baseUri}/api/v1.0/documents/{$invoice_uuid}/raw", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            return [
                'status'  => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    public function getInvoicePDF($invoice_uuid, $accessToken)
    {
        try {
            $response = $this->client->get("{$this->baseUri}/api/v1.0/documents/{$invoice_uuid}/pdf", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type'  => 'application/json',
                    'Accept-Language' => 'ar'
                ],
            ]);
            return (string)  $response->getBody() ;
        } catch (GuzzleException $e) {
            return null;
        }
    }
    
    /**
     * Get invoice details (Document Details)
     */
    // داخل class EInvoiceService

    public function getInvoiceDetails($uuid, $accessToken)
    {
        try {
            $response = $this->client->get("{$this->baseUri}/api/v1.0/documents/{$uuid}/details", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type'  => 'application/json',
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            return [
                'status' => true,
                'data'   => $result,
            ];

        } catch (GuzzleException $e) {
            return [
                'status'  => false,
                'message' => $e->getResponse()
                ? $e->getResponse()->getBody()->getContents()
                : $e->getMessage(),
            ];
        }
    }

}
