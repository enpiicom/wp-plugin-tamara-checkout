<?php

declare(strict_types=1);

namespace Tamara_Checkout\Deps\Tamara\Request\Merchant;

use Tamara_Checkout\Deps\Tamara\Request\AbstractRequestHandler;
use Tamara_Checkout\Deps\Tamara\Response\Merchant\GetPublicConfigsResponse;

class GetPublicConfigsRequestHandler extends AbstractRequestHandler
{
    private const MERCHANT_CONFIGS_ENDPOINT = '/merchants/configs';

    /**
     * @param GetPublicConfigsRequest $request
     * @return GetPublicConfigsResponse
     * @throws \Tamara_Checkout\Deps\Psr\Http\Client\ClientExceptionInterface
     * @throws \Tamara_Checkout\Deps\Tamara\Exception\RequestException
     */
    public function __invoke(GetPublicConfigsRequest $request)
    {
        $response = $this->httpClient->get(
            self::MERCHANT_CONFIGS_ENDPOINT
        );

        return new GetPublicConfigsResponse($response);
    }
}
