<?php

class ilViteroMultiPartSoapClient extends SoapClient
{
    /**
     * For multipart message we return the relvant SOAP-ENV part
     * @param     $request
     * @param     $location
     * @param     $action
     * @param     $version
     * @param int $one_way
     * @return false|string
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $response = parent::__doRequest($request, $location, $action, $version, $one_way);
        $start = strpos($response, '<SOAP-ENV:Envelope');
        $end = strrpos($response, '</SOAP-ENV:Envelope>');
        $purged_response = substr($response, $start, $end - $start + 20);
        return $purged_response;
    }
}