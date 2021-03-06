<?php
/**
 * Class and Function List:
 * Function list:
 * - AWSAuth()
 * - authenticate()
 * - getDriver()
 * - executeAction()
 * - getState()
 * - getSecurityGroups()
 * Classes list:
 * - CloudProvider
 */

class CloudProvider 
{
    private static $aws;
    private static $ec2Compute;
    private static function AWSAuth($account) {
        $credentials = json_decode($account->credentials);
        $config['key'] = $credentials->apiKey;
        $config['secret'] = $credentials->secretKey;
        $config['region'] = empty($credentials->instanceRegion) ? 'us-east-1' : $credentials->instanceRegion;
        $conStatus = FALSE;
        try {
            $ec2Client = \Aws\Ec2\Ec2Client::factory($config);
            self::$ec2Compute = $ec2Client;
            $result = $ec2Client->DescribeInstances(array(
                'Filters' => array(
                    array(
                        'Name' => 'instance-type',
                        'Values' => array(
                            'm1.small'
                        )
                    ) ,
                )
            ));
            
            $reservations = $result->toArray();
            if (isset($reservations['requestId'])) $conStatus = TRUE;
            else $conStatus = FALSE;
        }
        catch(Exception $ex) {
            $conStatus = FALSE;
            Log::error($ex);
        }
        return $conStatus;
    }
    
    public static function authenticate($account) {
        return self::getDriver($account)->authenticate();
    }
    
    public static function getDriver($account, $region = 'us-east-1') {
        $iProvider = '';
		switch ($account->cloudProvider) {
            case Constants::AWS_CLOUD:
                $iProvider = new AWSPRoviderImpl($account, $region);
                return $iProvider;
            break;
        }
    }
    
    public static function executeAction($instanceAction, $account, $instanceID, $region='us-east-1') {
        $response = '';
        switch ($instanceAction) {
            case 'start':
                
                $response = self::getDriver($account)->startInstances(array(
                    'DryRun' => false,
                    'InstanceIds' => array(
                        $instanceID
                    ) ,
                ));
            break;
            case 'stop':
                $response = self::getDriver($account)->stopInstances(array(
                    'DryRun' => false,
                    'InstanceIds' => array(
                        $instanceID
                    )
                ));
            break;
            case 'restart':
                $response = self::getDriver($account)->restartInstances(array(
                    'DryRun' => false,
                    'InstanceIds' => array(
                        $instanceID
                    )
                ));
            break;
            case 'terminate':
                $response = self::getDriver($account)->terminateInstances(array(
                    'DryRun' => false,
                    'InstanceIds' => array(
                        $instanceID
                    )
                ));
            break;
            case 'describeInstances':
                $response = self::getDriver($account,$region)->describeInstances(array(
                    'DryRun' => false,
                    'InstanceIds' => $instanceID));
            break;
            case 'getSecurityGroups':
                $response = self::getDriver($account)->describeSecurityGroups(array(
                    'DryRun' => false
                ));
            break;
            case 'downloadKey':
                $responseJson = xDockerEngine::authenticate(array(
                    'username' => Auth::user()->username,
                    'password' => md5(Auth::user()->engine_key)
                ));
                EngineLog::logIt(array(
                    'user_id' => Auth::id() ,
                    'method' => 'authenticate-executeAction',
                    'return' => $responseJson
                ));
                $obj = json_decode($responseJson);
                if (!empty($obj) && $obj->status == 'OK') {
                    
                    $response = xDockerEngine::downloadKey(array(
                        'token' => $obj->token,
                        'cloudProvider' => $account->cloudProvider,
                        'instanceRegion' => $account->instanceRegion
                    ));
                    if (StringHelper::isJson($response)) {
                        $response = json_decode($response, true);
                        $response['message'] = 'Key is returned in field key';
                    } else $response = array(
                        'status' => 'error',
                        'message' => 'Error occured while downloading keys'
                    );
                }
                if (!empty($obj) && $obj->status == 'error') {
                    Log::error('Error occured while downloading key' . $obj->message);
                    $response = array(
                        'status' => $obj->status,
                        'message' => 'Unexpected error! Contact Support'
                    );
                }
                break;
            }
            return $response;
    }
    
    public static function getState($cloudAccountId, $instanceID) {
        $account = CloudAccountHelper::findAndDecrypt($cloudAccountId);
        
        $data = self::executeAction('describeInstances', $account, $instanceID);

        if ($data['status'] == 'OK') {
            if (!empty($data['message']['Reservations'][0]['Instances'][0]['State']['Name'])) return UIHelper::getLabel($data['message']['Reservations'][0]['Instances'][0]['State']['Name']);
            else return UIHelper::getLabel('NA');
        } else if ($data['status'] == 'error') {
            return UIHelper::getLabel($data['status']);
        } else {
            return UIHelper::getLabel('NA');
        }
    }
    
    public static function getSecurityGroups($function, $cloudAccountId, $filter) {
        $account = CloudAccountHelper::findAndDecrypt($cloudAccountId);
        
        $data = self::executeAction($function, $account, $filter);
        if (!empty($data) && $data['status'] === 'OK') {
            return $data['message'];
        } else {
            return array();
        }
    }

     public static function startInstance($cloudAccountId, $instanceID) {
       $account = CloudAccountHelper::findAndDecrypt($cloudAccountId);
        
        $data = self::executeAction('start', $account, $instanceID);
        if ($data['status'] == 'OK') {
            return $data['message'];
        } else {
            return array();
        }
    }

    public static function stopInstance($cloudAccountId, $instanceID) {
        $account = CloudAccountHelper::findAndDecrypt($cloudAccountId);
        
        $data = self::executeAction('stop', $account, $instanceID);
        
        if ($data['status'] == 'OK') {
            return $data['message'];
        } else {
            return array();
        }
    }



    public static function getInstances($cloudAccountId, $instanceID,$region)
    {
        $account = CloudAccountHelper::findAndDecrypt($cloudAccountId);

        //$response = self::getDriver($account,$region)->describeInstancesall(array('DryRun' => false              'InstanceIds' => array('')));
        $response = self::executeAction('describeInstances', $account, $instanceID,$region);

        if (!empty($response) && $response['status'] === 'OK') {
            return $response['message'];
        } else {
            return array();
       }

    }


    public static function getEBS($cloudAccountId)
    {
        $account = CloudAccountHelper::findAndDecrypt($cloudAccountId);

         $response = self::getDriver($account)->describeVolumesall(array(
                    'DryRun' => false, 
                    'InstanceIds' => array('')));

        if (!empty($response) && $response['status'] === 'OK') {
            return $response['message'];
        } else {
            return array();
       }

    }

    public static function getSG($cloudAccountId)
    {
        $account = CloudAccountHelper::findAndDecrypt($cloudAccountId);

         $response = self::getDriver($account)->describeSGall(array(
                    'DryRun' => false, 
                    'InstanceIds' => array('')));

        if (!empty($response) && $response['status'] === 'OK') {
            return $response['message'];
        } else {
            return array();
       }

    }

    public static function getKP($cloudAccountId)
    {
        $account = CloudAccountHelper::findAndDecrypt($cloudAccountId);

         $response = self::getDriver($account)->describeKPall(array(
                    'DryRun' => false, 
                    'InstanceIds' => array('')));

        if (!empty($response) && $response['status'] === 'OK') {
            return $response['message'];
        } else {
            return array();
       }

    }

     public static function getTags($cloudAccountId)
    {
        $account = CloudAccountHelper::findAndDecrypt($cloudAccountId);

         $response = self::getDriver($account)->describeTags(array(
                    'DryRun' => false, 
                    'InstanceIds' => array('')));

        if (!empty($response) && $response['status'] === 'OK') {
            return $response['message'];
        } else {
            return array();
       }

    }


     public static function getSubnets($cloudAccountId)
    {
        $account = CloudAccountHelper::findAndDecrypt($cloudAccountId);

         $response = self::getDriver($account)->describeSubnets(array(
                    'DryRun' => false, 
                    'InstanceIds' => array('')));

        if (!empty($response) && $response['status'] === 'OK') {
            return $response['message'];
        } else {
            return array();
       }

    }

     public static function getVpcs($cloudAccountId)
    {
        $account = CloudAccountHelper::findAndDecrypt($cloudAccountId);

         $response = self::getDriver($account)->describeVpcs(array(
                    'DryRun' => false, 
                    'InstanceIds' => array('')));

        if (!empty($response) && $response['status'] === 'OK') {
            return $response['message'];
        } else {
            return array();
       }

    }


 public static function getSummary($account)
	{
		return self::getDriver($account)->getSummary();
	}
}
