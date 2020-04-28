<?php

namespace Omnipay\Skrill\Message;

use Composer\Cache;
use GuzzleHttp\Psr7\MessageTrait;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Skrill Payment Response
 *
 * This is the associated response to our PaymentRequest where we get Skrill's session,
 * and thus the URL to where we shall redirect users to the payment page.
 *
 * @author Joao Dias <joao.dias@cherrygroup.com>
 * @copyright 2013-2014 Cherry Ltd.
 * @license http://opensource.org/licenses/mit-license.php MIT
 * @version 6.5 Skrill Payment Gateway Integration Guide
 */
class PaymentResponse extends AbstractResponse implements RedirectResponseInterface
{
    use MessageTrait;

    /**
     * @return false
     */
    public function isSuccessful()
    {
        return false;
    }

    public function isRedirect()
    {
        return $this->getSessionId() !== null;
    }

    /**
     * @return string redirect url
     */
    public function getRedirectUrl()
    {
        return $this->getRequest()->getEndpoint() . '?sid=' . $this->getSessionId();
    }

    /**
     * @return string redirect method
     */
    public function getRedirectMethod()
    {
        return 'GET';
    }

    /**
     * @return null
     */
    public function getRedirectData()
    {
        return null;
    }

    /**
     * Get the session identifier to be submitted at the next step.
     *
     * @return string|null session id
     */
    public function getSessionId()
    {
        $data = preg_match('~TS014dc4bb=([0-9a-fA-F]+)~', $this->getSetCookie(), $matches)
            ? $matches[1]
            : null;

        if (!empty($data)) {
            return $data;
        }

        $data = preg_match('~SESSION_ID=([0-9a-fA-F]+)~', $this->getSetCookie(), $matches)
            ? $matches[1]
            : null;
        if (!empty($data)) {
            return $data;
        }

        return $this->getSetCustomCache();
    }

    /**
     * Get the skrill status of this response.
     *
     * @return string status
     */
    public function getStatus()
    {
        $data = $this->data->getHeader('X-Skrill-Status');
        return (string)!empty($data) && !is_array($data) ? $data : '';
    }

    /**
     * Get the status code.
     *
     * @return string|null status code
     */
    public function getCode()
    {
        $statusTokens = explode(':', $this->getStatus());
        return array_shift($statusTokens) ?: null;
    }

    /**
     * Get the status message.
     *
     * @return string|null status message
     */
    public function getMessage()
    {
        $statusTokens = explode(':', $this->getStatus());
        return array_pop($statusTokens) ?: null;
    }

    public function getSetCookie()
    {
        $data = $this->data->getHeader('Set-Cookie');
        return (string)!empty($data) && !is_array($data) ? $data : '';
    }

    public function getSetCustomCache()
    {
        $data = \Illuminate\Support\Facades\Cache::get('SESSION_ID');
        return (string)!empty($data) && !is_array($data) ? $data : session_id();
    }
}
