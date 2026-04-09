<?php

namespace FluentCampaign\App\Http\Controllers;

use FluentCampaign\App\Services\PluginManager\FluentLicensing;
use FluentCrm\App\Http\Controllers\Controller;
use FluentCrm\Framework\Request\Request;
use FluentCrm\Framework\Support\Arr;

class LicenseController extends Controller
{
    public function getStatus(Request $request)
    {

        $licenseManager = FluentLicensing::getInstance();

        $data = $licenseManager->getStatus(true);

        if (is_wp_error($data)) {
            $data = [
                'status'   => $data->get_error_code(),
                'error'    => true,
                'mnessage' => $data->get_error_message(),
            ];
        }

        $status = Arr::get($data, 'status');

        if ($status == 'expired') {
            $data['renew_url'] = $licenseManager->getRenewUrl();
        }

        $data['purchase_url'] = $licenseManager->getConfig('purchase_url');

        unset($data['license_key']);
        return $data;
    }

    public function saveLicense(Request $request)
    {

        $licenseManager = FluentLicensing::getInstance();
        $licenseKey = $request->get('license_key');

        $response = $licenseManager->activate($licenseKey);
        if (is_wp_error($response)) {
            return $this->sendError([
                'message' => $response->get_error_message()
            ]);
        }

        return [
            'license_data' => $response,
            'message'      => __('Your license key has been successfully updated', 'fluentcampaign-pro')
        ];
    }

    public function deactivateLicense(Request $request)
    {

        $licenseManager = FluentLicensing::getInstance();

        $response = $licenseManager->deactivate();
        if (is_wp_error($response)) {
            return $this->sendError([
                'message' => $response->get_error_message()
            ]);
        }

        unset($response['license_key']);

        return [
            'license_data' => $response,
            'message'      => __('Your license key has been successfully deactivated', 'fluentcampaign-pro')
        ];
    }

}
