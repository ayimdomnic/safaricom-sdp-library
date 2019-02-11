<?php

class SDP
{
   /*
	* generatePassword - generates password used in sending requests to SDP
	*
	* @param string sp_timestamp a string reprenting the timestamp of sending the message
	* @return string generated password
	*/
    private static function generatePassword($sp_timestamp)
    {
        if (!isset($sp_timestamp) || empty($sp_timestamp)) {
            # code...
            $sp_timestamp = date('YmdHiss');
        }

        return md5(config("sdp.sp_id").config('sdp.sp_password').$sp_timestamp);

    }

    private static function sendSms($kmp_service_id, $kmp_recipients,$kmp_correlator,$kmp_code,$kmp_message,$kmp_linkid='')
    {
        $kmp_spid = config('sdp.sp_id');
        $kmp_timestamp = date("YmdHis");
        $kmp_sspwd = self::generatePassword($kmp_timestamp);

        $bodyxml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v2="http://www.huawei.com.cn/schema/common/v2_1" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/send/v2_2/local"> <soapenv:Header> <v2:RequestSOAPHeader><spId>'.$kmp_spid.'</spId><spPassword>'.$kmp_sppwd.'</spPassword><serviceId>'.$kmp_service_id.'</serviceId><timeStamp>'.$kmp_timestamp.'</timeStamp>';
					
		if(!empty($kmp_linkid)){
			$bodyxml.='<v2:linkid>'.$kmp_linkid.'</v2:linkid>';
        }
        
        if (isset($kmp_recipients)) {
            # code...
            if(count($kmp_recipients) == 1) {
                $bodyxml.='<v2:OA>'.$kmp_recipients.'</v2:OA><v2:FA>'.$kmp_recipients.'</v2:FA>';
            }
        } else {
            return [
                "ResultCode" => "4",
                'ResultDesc'=>"Recipient(s) empty.",
                'ResultDetails'=>"No recipient address(es) specified."
            ];
        }

        $bodyxml.='</v2:RequestSOAPHeader></soapenv:Header><soapenv:Body><loc:sendSms>';
		
		//specify the address of the recipient
		$count=count($kmp_recipients);
		if($count == 1){ //one recipient
			$bodyxml.='<loc:addresses>'.$kmp_recipients.'</loc:addresses>';
		}
		else if($count >  config('sdp.SEND_SMS_MAXIMUM_RECIPIENTS')){ //too many recipients
			return [
                'ResultCode'=>"5",
                'ResultDesc'=>"Too many recipients.",
                'ResultDetails'=>"The number of recipients exceeds the maximum number."
            ]; 
		}
		else{ //more than one recipients
			foreach ($kmp_recipients as $misdn){
				$bodyxml.='<loc:addresses>'.$misdn.'</loc:addresses>';
			}
		}
		
		//specify the last part of the soap request
        $bodyxml.=	'<loc:senderName>'.$kmp_code.'</loc:senderName><loc:message>'.$kmp_message.'</loc:message>';
        
        if( Config::get('SEND_SMS_DEFAULT_DELIVERY_NOTIFICATION_FLAG') == 1){
			$bodyxml.=	'<loc:receiptRequest><endpoint>'.Config::get('SEND_SMS_DEFAULT_DELIVERY_NOTIFICATION_ENDPOINT').'</endpoint><interfaceName>SmsNotification</interfaceName><correlator>'.$kmp_correlator.'</correlator></loc:receiptRequest>';
		}
		
		$bodyxml.=	'</loc:sendSms></soapenv:Body></soapenv:Envelope>';
		
					
		//Create the nusoap client and set the parameters, endpoint specified in the client_inc.php
		$client = new nusoap_client(config('sdp.SEND_SMS_DEFAULT_SERVICE_ENDPOINT'),true);	
		$bsoapaction = "";
		$client->soap_defencoding = 'utf-8';
		$client->useHTTPPersistentConnection();
		
		//Send the soap request to the server
        $result = $client->send($bodyxml, $bsoapaction);
        
        //Since I am using Laravel I will Log the Information Here
        
    }
}