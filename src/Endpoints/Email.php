<?php

namespace SenderNet\Endpoints;

use SenderNet\Helpers\Builder\EmailParams;
use SenderNet\Helpers\GeneralHelpers;
use SenderNet\Helpers\EmailPayloadBuilder;

class Email extends AbstractEndpoint
{
    protected string $endpoint = 'message/send';

    /**
     * @throws \JsonException
     * @throws \SenderNet\Exceptions\SenderNetAssertException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function send(EmailParams $params): array
    {
        GeneralHelpers::validateEmailParams($params);

        return $this->httpLayer->post(
            $this->buildUri($this->endpoint),
            EmailPayloadBuilder::fromEmailParams($params)
        );
    }
}
