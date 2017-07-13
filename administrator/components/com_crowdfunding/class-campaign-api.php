<?php
   /**
    * @version $Id$
    * @copyright 2017 Jacco Drabbe
    **/

   /**
    * CampaignAPI
    *
    * @author Jacco Drabbe <jacco@qurl.nl>
    * @copyright 2017 Qurl
    * @access public
    *
    */
   class CampaignAPI {
      private $url = 'http://crowdfund.projectaware.nl';
      private $api = '/api/v1';
      private $method;
      private $bearer = 'ddff16f52e527bf77aee3f88434dd350fb5ab1998c17d92431efdc1b06fc8474';

      /**
       * CampaignAPI constructor.
       *
       * @throws Exception
       */
      public function __construct() {
         // check for cURL, bail when not found
         if (! function_exists('curl_version') ) {
            throw new Exception('cURL not available');
         }
      }

      /**
       * CampaignAPI::getCampaignBackers() Get Campaign backers
       *
       * @param int $id Campaign ID
       * @return array|bool
       */
      public function getCampaignBackers($id) {
         if (! is_numeric($id) ) {
            return FALSE;
         }

         $this->method = 'GET';
         $module = '/campaign/' . $id . '/backers';
         $response = $this->sendToServer($module);
   		//echo ('<br /> bli: ' . var_dump($response));

         return $response;
      }

      /**
       * CampaignAPI::getCampaignDetails() Get Campaign details
       *
       * @param int $id Campaign ID
       * @return bool|object
       */
      public function getCampaignDetails($id) {
        if (! is_numeric($id) ) {
           return FALSE;
        }

        $this->method = 'GET';
        $module = '/campaign/' . $id;
        $response = $this->sendToServer($module);
            //echo ('<br />' . var_dump($response));

        return $response;
      }

      /**
       * CampaignAPI::getCampaigns() Get campaigns
       *
       * @return array
       */
      public function getCampaigns() {
         $this->method = 'GET';
         $module = '/campaign';
         $response = $this->sendToServer($module);

         return $response;
      }

      /**
       * CampaignAPI::sendToServer() Send request to server
       *
       * @param string $module Module name
       * @param array $data Post data
       * @return mixed
       */
      private function sendToServer($module, $data = array()) {
         $url = $this->url . $this->api . $module;
         $ch = curl_init();

         curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
         curl_setopt($ch, CURLOPT_HEADER, FALSE);

         // Bearer token
         $headers = array(
            'Authorization: Bearer ' . $this->bearer
         );

         if ( $this->method == 'POST' ) {
            $jsondata = json_encode($data);
            $headers[ ] = 'Content-Type: application/json';

            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata);
         }

         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

         $result = curl_exec($ch);
         curl_close($ch);

         $result = json_decode($result);
         return $result;
      }

      /**
       * CampaignAPI::setFund() Set back funding
       *
       * @param string $email Backer email address
       * @param int $campaign_id Campaign ID
       * @param float $amount Amount of backing
       * @return object|bool
       */
      public function setFund($email, $campaign_id, $amount) {
         if (! filter_var($email, FILTER_VALIDATE_EMAIL) ) {
            return FALSE;
         }

         if (! is_numeric($campaign_id) ) {
            return FALSE;
         }

         if (! is_float($amount) ) {
            //return FALSE;
         }
         $data = array(
            'email'         => $email,
            'amount'        => (float) $amount,
            'timestamp'     => time(),
         );

         $this->method = 'POST';
         $module = '/campaign/' . $campaign_id . '/fund';
         $response = $this->sendToServer($module, $data);

         return $response;
      }
   }
?>
