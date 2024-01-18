<?php

namespace App\Http\Livewire;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use App\Models\Setting;
use App\Helpers\Helper;

class SlackSettingsForm extends Component
{
    public $webhook_endpoint;
    public $webhook_channel;
    public $webhook_botname;
    public $isDisabled ='disabled' ;
    public $webhook_name;
    public $webhook_link;
    public $webhook_placeholder;
    public $webhook_icon;
    public $webhook_selected;
    public array $webhook_text;

    public Setting $setting;

    public $webhook_endpoint_rules;


    protected $rules = [
        'webhook_endpoint'                      => 'required_with:webhook_channel|starts_with:http://,https://,ftp://,irc://,https://hooks.slack.com/services/|url|nullable',
        'webhook_channel'                       => 'required_with:webhook_endpoint|starts_with:#|nullable',
        'webhook_botname'                       => 'string|nullable',
    ];
    public $messages = [
        'webhook_endpoint.starts_with'          => 'your webhook endpoint should begin with http://, https:// or other protocol.',
    ];

    public function mount() {
        $this->webhook_text= [
            "slack" => array(
                "name" => trans('admin/settings/general.slack') ,
            "icon" => 'fab fa-slack',
            "placeholder" => "https://hooks.slack.com/services/XXXXXXXXXXXXXXXXXXXXX",
            "link" => 'https://api.slack.com/messaging/webhooks',
        ),
            "general"=> array(
                "name" => trans('admin/settings/general.general_webhook'),
                "icon" => "fab fa-hashtag",
                "placeholder" => "Insert URL",
                "link" => "",
            ),
            "microsoft" => array(
                "name" => trans('admin/settings/general.ms_teams'),
                "icon" => "fa-brands fa-microsoft",
                "placeholder" => "https://abcd.webhook.office.com/webhookb2/XXXXXXX",
                "link" => "https://learn.microsoft.com/en-us/microsoftteams/platform/webhooks-and-connectors/how-to/add-incoming-webhook?tabs=dotnet#create-incoming-webhooks-1",
            ),
        ];

        $this->setting = Setting::getSettings();
        $this->save_button = trans('general.save');
        $this->webhook_selected = $this->setting->webhook_selected;
        $this->webhook_name = $this->webhook_text[$this->setting->webhook_selected]["name"];
        $this->webhook_icon = $this->webhook_text[$this->setting->webhook_selected]["icon"];
        $this->webhook_placeholder = $this->webhook_text[$this->setting->webhook_selected]["placeholder"];
        $this->webhook_link = $this->webhook_text[$this->setting->webhook_selected]["link"];
        $this->webhook_endpoint = $this->setting->webhook_endpoint;
        $this->webhook_channel = $this->setting->webhook_channel;
        $this->webhook_botname = $this->setting->webhook_botname;
        $this->webhook_options = $this->setting->webhook_selected;


        if($this->setting->webhook_endpoint != null && $this->setting->webhook_channel != null){
            $this->isDisabled= '';
        }

    }
    public function updated($field) {

            $this->validateOnly($field, $this->rules);

    }

    public function updatedWebhookSelected() {
        $this->webhook_name = $this->webhook_text[$this->webhook_selected]['name'];
        $this->webhook_icon = $this->webhook_text[$this->webhook_selected]["icon"]; ;
        $this->webhook_placeholder = $this->webhook_text[$this->webhook_selected]["placeholder"];
        $this->webhook_endpoint = null;
        $this->webhook_link = $this->webhook_text[$this->webhook_selected]["link"];
        if($this->webhook_selected != 'slack'){
            $this->isDisabled= '';
            $this->save_button = trans('general.save');
        }

    }

    private function isButtonDisabled() {
            if (empty($this->webhook_endpoint)) {
                $this->isDisabled = 'disabled';
                $this->save_button = trans('admin/settings/general.webhook_presave');
            }
            if (empty($this->webhook_channel)) {
                $this->isDisabled = 'disabled';
                $this->save_button = trans('admin/settings/general.webhook_presave');
            }
    }

    public function render()
    {
        $this->isButtonDisabled();
        return view('livewire.slack-settings-form');
    }

    public function testWebhook(){

        $webhook = new Client([
            'base_url' => e($this->webhook_endpoint),
            'defaults' => [
                'exceptions' => false,
            ],
            'allow_redirects' => false,
        ]);

        $payload = json_encode(
            [
                'channel'    => e($this->webhook_channel),
                'text'       => trans('general.webhook_test_msg', ['app' => $this->webhook_name]),
                'username'    => e($this->webhook_botname),
                'icon_emoji' => ':heart:',

            ]);

        try {
            $test = $webhook->post($this->webhook_endpoint, ['body' => $payload]);

            if(($test->getStatusCode() == 302)||($test->getStatusCode() == 301)){
                return session()->flash('error' , trans('admin/settings/message.webhook.error_redirect', ['endpoint' => $this->webhook_endpoint]));
            }
            $this->isDisabled='';
            $this->save_button = trans('general.save');
            return session()->flash('success' , trans('admin/settings/message.webhook.success', ['webhook_name' => $this->webhook_name]));

        } catch (\Exception $e) {

            $this->isDisabled='disabled';
            $this->save_button = trans('admin/settings/general.webhook_presave');
            return session()->flash('error' , trans('admin/settings/message.webhook.error', ['error_message' => $e->getMessage(), 'app' => $this->webhook_name]));
        }

        return session()->flash('error' , trans('admin/settings/message.webhook.error_misc'));

    }

    public function clearSettings(){

        if (Helper::isDemoMode()) {
            session()->flash('error',trans('general.feature_disabled'));
        } else {
            $this->webhook_endpoint = '';
            $this->webhook_channel = '';
            $this->webhook_botname = '';
            $this->setting->webhook_endpoint = '';
            $this->setting->webhook_channel = '';
            $this->setting->webhook_botname = '';

            $this->setting->save();

            session()->flash('success', trans('admin/settings/message.update.success'));
        }
    }

    public function submit()
    {
        if (Helper::isDemoMode()) {
            session()->flash('error',trans('general.feature_disabled'));
        } else {
            $this->validate($this->rules);

            $this->setting->webhook_selected = $this->webhook_selected;
            $this->setting->webhook_endpoint = $this->webhook_endpoint;
            $this->setting->webhook_channel = $this->webhook_channel;
            $this->setting->webhook_botname = $this->webhook_botname;

            $this->setting->save();

            session()->flash('success',trans('admin/settings/message.update.success'));
        }

    }
     public function msTeamTestWebhook(){

     $payload =
        [
            "@type" => "MessageCard",
            "@context" => "http://schema.org/extensions",
            "summary" => "Snipe-IT Integration Test Summary",
            "title" => "Snipe-IT Integration Test",
            "text" => trans('general.webhook_test_msg', ['app' => $this->webhook_name]),
        ];

         try {
         $response = Http::withHeaders([
             'content-type' => 'applications/json',
         ])->post($this->webhook_endpoint,
            $payload)->throw();

         if(($response->getStatusCode() == 302)||($response->getStatusCode() == 301)){
             return session()->flash('error' , trans('admin/settings/message.webhook.error_redirect', ['endpoint' => $this->webhook_endpoint]));
         }
         $this->isDisabled='';
         $this->save_button = trans('general.save');
         return session()->flash('success' , trans('admin/settings/message.webhook.success', ['webhook_name' => $this->webhook_name]));

     } catch (\Exception $e) {

         $this->isDisabled='disabled';
         $this->save_button = trans('admin/settings/general.webhook_presave');
         return session()->flash('error' , trans('admin/settings/message.webhook.error', ['error_message' => $e->getMessage(), 'app' => $this->webhook_name]));
     }

         return session()->flash('error' , trans('admin/settings/message.webhook.error_misc'));
     }
}
