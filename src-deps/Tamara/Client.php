<?php

namespace Tamara_Checkout\Deps\Tamara;

use Tamara_Checkout\Deps\Tamara\HttpClient\HttpClient;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\CheckPaymentOptionsAvailabilityRequest;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\CreateCheckoutRequest;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\GetPaymentTypesRequest;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\GetPaymentTypesV2Request;
use Tamara_Checkout\Deps\Tamara\Request\Merchant\GetPublicConfigsRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\AuthoriseOrderRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\CancelOrderRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderByReferenceIdRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\UpdateReferenceIdRequest;
use Tamara_Checkout\Deps\Tamara\Request\Payment\CaptureRequest;
use Tamara_Checkout\Deps\Tamara\Request\Payment\RefundRequest;
use Tamara_Checkout\Deps\Tamara\Request\Payment\SimplifiedRefundRequest;
use Tamara_Checkout\Deps\Tamara\Request\RequestDispatcher;
use Tamara_Checkout\Deps\Tamara\Request\Webhook\RegisterWebhookRequest;
use Tamara_Checkout\Deps\Tamara\Request\Webhook\RemoveWebhookRequest;
use Tamara_Checkout\Deps\Tamara\Request\Webhook\RetrieveWebhookRequest;
use Tamara_Checkout\Deps\Tamara\Request\Webhook\UpdateWebhookRequest;
use Tamara_Checkout\Deps\Tamara\Response\Checkout\CheckPaymentOptionsAvailabilityResponse;
use Tamara_Checkout\Deps\Tamara\Response\Checkout\CreateCheckoutResponse;
use Tamara_Checkout\Deps\Tamara\Response\Checkout\GetPaymentTypesResponse;
use Tamara_Checkout\Deps\Tamara\Response\Merchant\GetPublicConfigsResponse;
use Tamara_Checkout\Deps\Tamara\Response\Order\AuthoriseOrderResponse;
use Tamara_Checkout\Deps\Tamara\Response\Order\GetOrderByReferenceIdResponse;
use Tamara_Checkout\Deps\Tamara\Response\Order\GetOrderResponse;
use Tamara_Checkout\Deps\Tamara\Response\Order\UpdateReferenceIdResponse;
use Tamara_Checkout\Deps\Tamara\Response\Payment\CancelResponse;
use Tamara_Checkout\Deps\Tamara\Response\Payment\CaptureResponse;
use Tamara_Checkout\Deps\Tamara\Response\Payment\RefundResponse;
use Tamara_Checkout\Deps\Tamara\Response\Payment\SimplifiedRefundResponse;
use Tamara_Checkout\Deps\Tamara\Response\Webhook\RegisterWebhookResponse;
use Tamara_Checkout\Deps\Tamara\Response\Webhook\RemoveWebhookResponse;
use Tamara_Checkout\Deps\Tamara\Response\Webhook\RetrieveWebhookResponse;
use Tamara_Checkout\Deps\Tamara\Response\Webhook\UpdateWebhookResponse;

class Client
{
    /**
     * @var string
     */
    public const VERSION = '2.0.4';

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var RequestDispatcher
     */
    private $requestDispatcher;

    /**
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->requestDispatcher = new RequestDispatcher($httpClient);
    }

    /**
     * @param Configuration $configuration
     *
     * @return Client
     */
    public static function create(Configuration $configuration): Client
    {
        return new static($configuration->createHttpClient());
    }

    /**
     * @param string $countryCode
     * @param string $currency
     *
     * @return GetPaymentTypesResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function getPaymentTypes(string $countryCode, string $currency = ''): GetPaymentTypesResponse
    {
        return $this->requestDispatcher->dispatch(new GetPaymentTypesRequest($countryCode, $currency));
    }

    /**
     * @param GetPaymentTypesV2Request $request
     *
     * @return GetPaymentTypesResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function getPaymentTypesV2(GetPaymentTypesV2Request $request): GetPaymentTypesResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * @param CreateCheckoutRequest $createCheckoutRequest
     *
     * @return CreateCheckoutResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function createCheckout(CreateCheckoutRequest $createCheckoutRequest): CreateCheckoutResponse
    {
        return $this->requestDispatcher->dispatch($createCheckoutRequest);
    }

    /**
     * @param AuthoriseOrderRequest $authoriseOrderRequest
     *
     * @return AuthoriseOrderResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function authoriseOrder(AuthoriseOrderRequest $authoriseOrderRequest): AuthoriseOrderResponse
    {
        return $this->requestDispatcher->dispatch($authoriseOrderRequest);
    }

    /**
     * @param CancelOrderRequest $cancelOrderRequest
     *
     * @return CancelResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function cancelOrder(CancelOrderRequest $cancelOrderRequest): CancelResponse
    {
        return $this->requestDispatcher->dispatch($cancelOrderRequest);
    }

    /**
     * @param CaptureRequest $captureRequest
     *
     * @return CaptureResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function capture(CaptureRequest $captureRequest): CaptureResponse
    {
        return $this->requestDispatcher->dispatch($captureRequest);
    }

    /**
     * @param RefundRequest $refundRequest
     *
     * @return RefundResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function refund(RefundRequest $refundRequest): RefundResponse
    {
        return $this->requestDispatcher->dispatch($refundRequest);
    }

    /**
     * @param RegisterWebhookRequest $request
     * @return RegisterWebhookResponse
     * @throws Exception\RequestDispatcherException
     */
    public function registerWebhook(RegisterWebhookRequest $request): RegisterWebhookResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * @param RetrieveWebhookRequest $request
     *
     * @return RetrieveWebhookResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function retrieveWebhook(RetrieveWebhookRequest $request): RetrieveWebhookResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * @param RemoveWebhookRequest $request
     *
     * @return RemoveWebhookResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function removeWebhook(RemoveWebhookRequest $request): RemoveWebhookResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * @param UpdateWebhookRequest $request
     *
     * @return UpdateWebhookResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function updateWebhook(UpdateWebhookRequest $request): UpdateWebhookResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * @param UpdateReferenceIdRequest $request
     *
     * @return UpdateReferenceIdResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function updateOrderReferenceId(UpdateReferenceIdRequest $request): UpdateReferenceIdResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * @param GetOrderByReferenceIdRequest $request
     *
     * @return GetOrderByReferenceIdResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function getOrderByReferenceId(GetOrderByReferenceIdRequest $request): GetOrderByReferenceIdResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * Get order details by tamara order id
     *
     * @param GetOrderRequest $request
     *
     * @return GetOrderResponse
     *
     * @throws Exception\RequestDispatcherException
     */
    public function getOrder(GetOrderRequest $request): GetOrderResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * Check if there are any available payment options for customer with the given order value
     * @param CheckPaymentOptionsAvailabilityRequest $request
     * @return CheckPaymentOptionsAvailabilityResponse
     * @throws Exception\RequestDispatcherException
     */
    public function checkPaymentOptionsAvailability(CheckPaymentOptionsAvailabilityRequest $request): CheckPaymentOptionsAvailabilityResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * Get merchant configs information
     * @param GetPublicConfigsRequest $request
     * @return GetPublicConfigsResponse
     * @throws Exception\RequestDispatcherException
     */
    public function getMerchantPublicConfigs(GetPublicConfigsRequest $request): GetPublicConfigsResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }

    /**
     * Make a refund using fewer parameters
     * @param SimplifiedRefundRequest $request
     * @return SimplifiedRefundResponse
     * @throws Exception\RequestDispatcherException
     */
    public function simplifyRefund(SimplifiedRefundRequest $request): SimplifiedRefundResponse
    {
        return $this->requestDispatcher->dispatch($request);
    }
}
