<?php

declare(strict_types = 1);
namespace modules\products\vps\integrations\SolusVMKVM;

/**
 * -------------------------------------------------------------------------------------
 * SolusVM KVM
 * 
 * Author		: 	DataXL B.V. - Bas van den Hoogen	
 * Copyright	: 	MIT License 2020	
 * Version 		:	1.0.0
 * 
 * CHANGE LOG:
 * -------------------------------------------------------------------------------------
 *  2020-10-16		DataXL B.V. - Bas van den Hoogen 		Initial version 1.0.0
 *  2020-11-11		DataXL B.V. - Bas van den Hoogen 		Initial version KVM 1.0.0
 * -------------------------------------------------------------------------------------
 */

class SolusVMKVM
{
    // use these to connect to VPS platform
    public $ServerURL, $ServerUser, $ServerPass;
    
    public $Error;
    public $Warning;
    public $Success;

    public function __construct()
    {
        $this->Error    = array();
        $this->Warning  = array();
        $this->Success  = array();
        
        $this->loadLanguageArray(LANGUAGE_CODE);
    }
    
    /**
	 * Use this function to prefix all errors messages with your VPS platform
	 * 
	 * @param 	string	 $message	The error message 
	 * @return 	boolean 			Always false
	 */
    private function __parseError(string $message)
	{
		$this->Error[] = 'SolusVM: ' . $message;
		return FALSE;	
    }
    
    /**
	 * Use this function to make a connection with SolusVM
	 * 
     * @param 	array	 $postfields  Array with postfields send to SolusVM API
     * @return  array                 Json on rdtype; array otherwise.
	 */
    private function doCall(array $postfields)
    {
        $postfields['id'] = $this->ServerUser;
        $postfields['key'] = $this->ServerPass;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->ServerURL.'/command.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        $data = curl_exec($ch);
        curl_close($ch);
        // Parse the returned data and build an array
        preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $match);
        if (isset($postfields['rdtype'])) {
            return $data;
        }
        $result = array();
        foreach ($match[1] as $x => $y) {
            $result[$y] = $match[2][$x];
        }

        return $result;
    }
    
	/**
	 * Get list of templates from the VPS platform
	 * 
	 * @return 	array 	List of templates
	 */    
    public function getTemplateList()
    {
        /**
    	 * Step 1) get template list
    	 */
        $response = json_decode($this->doCall(['action' => 'list-plans', 'type' => 'kvm', 'rdtype' => 'json']), true);
        
        /**
    	 * Step 2) provide feedback to HostFact
    	 */
        if($response)
        {
            if(count($response['plans']) == 0)
            {
                return $this->__parseError(__('node has no templates', 'SolusVM'));
            }
            
            $templates  = array();
            $i          = 0;
            // loop through templates and build return array
            foreach($response['plans'] as $template)
            {
                $templates[$i]['templateid']    = $template['id'];
                $templates[$i]['templatename']  = $template['name'];
                $templates[$i]['memory']        = $template['ram'] / 1024 / 1024;
                $templates[$i]['diskspace']     = $template['disk'] / 1024 / 1024 / 1024;
                $templates[$i]['cpucores']      = $template['cpus'];
                $i++;
            }

            return $templates;
        }
        
        return FALSE;
    }
    
    /**
	 * Perform an action on the VPS (eg pause, start, restart)
	 * 
     * @param 	string	 $vps_id      ID of the VPS on the VPS platform
     * @param 	string	 $action      Type of action
	 * @return 	boolean               True on success; False otherwise.
	 */
    public function doServerAction(string $vps_id, string $action)
    {        
        switch($action)
        {
            case 'pause':
                $response = $this->doCall([
                    'action'    => 'vserver-shutdown',
                    'vserverid' => $vps_id,
                ]);
            break;
            
            case 'start':
                $response = $this->doCall([
                    'action'    => 'vserver-boot',
                    'vserverid' => $vps_id,
                ]);
            break;
            
            case 'restart':
                $response = $this->doCall([
                    'action'    => 'vserver-reboot',
                    'vserverid' => $vps_id,
                ]);
            break;
        }
        
        /**
    	 * Step 2) provide feedback to HostFact
    	 */
        if($response && $response['status'] == 'success')
        {
            return TRUE;
        }
        else
        {
            $this->__parseError($response['statusmsg']);
            return FALSE;
        }
    }
    
    /**
	 * Get template details from VPS platform
	 * 
     * @param 	string   $template_id   ID of the template on the VPS platform
	 * @return 	array    Array with template details
	 */    
    public function getTemplate(string $template_id)
    {     
        /**
    	 * Step 1) get template
    	 */
        $response = json_decode($this->doCall(['action' => 'list-plans', 'type' => 'kvm', 'rdtype' => 'json']), true);
        
        /**
    	 * Step 2) provide feedback to HostFact
    	 */
        if(!$response)
        {
            return $this->__parseError(__('node has no templates', 'SolusVM'));
        }

        $plan = array();
        foreach($response['plans'] as $solusvmplan)
        {
            if($solusvmplan['id'] == $template_id)
            {
                $plan = $solusvmplan;
                break;
            }
        }

        if(!$plan)
        {
            return $this->__parseError(__('node has no templates', 'SolusVM'));
        }
        
        $template  = array();
        $template['templateid']    = $plan['id'];
        $template['templatename']  = $plan['name'];
        $template['memory']        = $plan['ram'] / 1024 / 1024;
        $template['diskspace']     = $plan['disk'] / 1024 / 1024 / 1024;
		$template['bandwidth']     = $plan['bandwidth'] / 1024 / 1024 / 1024;
        $template['cpucores']      = $plan['cpus'];

        return $template;
    }
    
	/**
	 * Get list of images from the VPS platform
	 * 
	 * @return 	array 	List of images
	 */    
    public function getImageList()
    {
        /**
    	 * Step 1) get images list
    	 */
        $response = $this->doCall(['action' => 'listtemplates', 'listpipefriendly' => true, 'type' => 'kvm']);
        
        /**
    	 * Step 2) provide feedback to HostFact
    	 */                
        if($response)
        {
            $images = array();
            $i      = 0;
            // loop through images and build return array
            foreach(explode(',', $response['templateskvm']) as $image)
            {   
                $image = explode('|', $image);
                $images[$i]['imageid']    = $image[0];
                $images[$i]['imagename']  = $image[1];
                $i++;
            }
            
            if(count($images) == 0)
            {
                return $this->__parseError(__('node has no images', 'SolusVM'));
            }

            return $images;
        }
        
        $this->__parseError($response['statusmsg']);
        return FALSE;
    }
    
    /**
	 * Validate the VPS server login credentials
	 * 
	 * @return 	boolean               True on success; False otherwise.
	 */    
    public function validateLogin()
    {
        /**
    	 * Step 1) send login or get command to check if login credentials are correct
         * 
    	 */
        $response = $this->doCall([]);
        if($response['status'] == 'error')
        {
            return FALSE;
        }
        
        return TRUE;
    }
    
    /**
     * This function makes it possible to provide additional settings or notes on the create and edit page of a VPS server within HostFact.
     * This may be necessary if more information is needed than just the URL, username and password of the platform. 
	 * 
     * @param   string     $edit_or_show   edit|show; determines if we are adding/editing or showing a VPS server 
	 * @return 	string     $html           input HTML
	 */
    public function showSettingsHTML(string $edit_or_show = 'edit')
	{
        $html = '';
        /**
    	 * Step 1) build html based on $edit_or_show
         * you can use $this->ServerSettings->InputName to request the settings variabeles, see examples below
         * 
    	 */
		if($edit_or_show == 'show')
		{
			// Show page
			$html   =   '<strong class="title2">Node Group ID</strong>' .  
					    '<span class="title2_value">' . 
					    ((isset($this->ServerSettings->NodeGroup)) ? htmlspecialchars($this->ServerSettings->NodeGroup) : '') . 
                        '</span>' ;
            $html   .=  '<strong class="title2">Client username</strong>'.
                        '<span class="title2_value">'.
                        ((isset($this->ServerSettings->ClientUser)) ? htmlspecialchars($this->ServerSettings->ClientUser) : '').
                        '</span>';
		}
		else
		{
            // add/edit page
            $html   =   '<strong class="title">Node Group (ID)</strong>'.
                        '<input type="text" name="module[vps][Settings][NodeGroup]" class="text1 size1" value="'.((isset($this->ServerSettings->NodeGroup)) ? htmlspecialchars($this->ServerSettings->NodeGroup) : '').'" />';
            $html   .=  '<strong class="title">Client username</strong>'.
                        '<input type="text" name="module[vps][Settings][ClientUser]" class="text1 size1" value="'.((isset($this->ServerSettings->ClientUser)) ? htmlspecialchars($this->ServerSettings->ClientUser) : '').'" />';
		}
        
        /**
    	 * Step 2) provide feedback to HostFact
    	 */ 
        
		return $html;
	}

    /**
	 * Create a VPS on the VPS platform
	 *  
	 * @return 	array               Return array with VPS ID on success; False on fail;
	 */    
    public function createVPS()
    {
        /**
    	 * Step 1) send create command
         * 
    	 */
        if(!$this->TemplateID)
        {
            $response = $this->doCall([
                'action'            => 'vserver-create',
                'type'              => 'kvm',
                'nodegroup'         => $this->ServerSettings->NodeGroup,
                'hostname'          => $this->VPSName,
                'password'          => $this->Password,
                'username'          => $this->ServerSettings->ClientUser,
                'plan'              => 'Custom',
                'custommemory'      => $this->MemoryMB.':'.$this->MemoryMB, // 256:500 where 256 - Guaranteed RAM and 500 - Burstable to RAM
                'customdiskspace'   => $this->DiskSpaceGB,
                'customcpu'         => $this->CPUCores,
                'template'          => $this->Image,
                'ips'               => 1,
            ]);
        }
        else
        {
            $template = $this->getTemplate($this->TemplateID);
            $response = $this->doCall([
                'action'    => 'vserver-create',
                'type'      => 'kvm',
                'nodegroup' => $this->ServerSettings->NodeGroup,
                'hostname'  => $this->VPSName,
                'password'  => $this->Password,
                'username'  => $this->ServerSettings->ClientUser,
                'plan'      => $template['templatename'],
                'template'  => $this->Image,
                'ips'       => 1,
            ]);
        }     

        /**
    	 * Step 2) provide feedback to HostFact
    	 */ 
        if($response && $response['status'] == 'success' && isset($response['vserverid']))
        {
            $vps = array();
            $vps['id'] = $response['vserverid'];
                                                 
            return $vps;
        }
        else
        {
            $this->__parseError($response['statusmsg']);
            return FALSE;
        }
    }
    
    /**
	 * Remove a VPS from the VPS platform
	 * 
     * @param 	string	 $vps_id      ID of the VPS on the VPS platform
	 * @return 	boolean               True on success; False otherwise.
	 */
    public function delete(string $vps_id)
    {
        /**
    	 * Step 1) send delete command
         * 
    	 */
        $response = $this->doCall([
            'action'       => 'vserver-terminate',
            'vserverid'    => $vps_id,
            'deleteclient' => false,
        ]);       

        /**
    	 * Step 2) provide feedback to HostFact
    	 */
        if($response && $response['status'] == 'success')
        {
            return TRUE;
        }
        else
        {
            $this->__parseError($response['statusmsg']);
            return FALSE;
        }
    }
    
    /**
	 * Function to support multiple languages for return messages
     * use __('your message', 'SolusVM'); to translate a message based on the language of HostFact
	 * 
     * @param 	string	 $language_code   Language code
	 */
   	public function loadLanguageArray(string $language_code)
	{
		$_LANG = array();

		switch($language_code)
		{
			case 'nl_NL':
                $_LANG['node gave no response']                 = 'Server gaf geen antwoord terug.';
                $_LANG['node returned error']                   = 'Server gaf een error terug.';
                $_LANG['node returned wrong data']              = 'Server gaf een antwoord terug, maar niet de benodigde data.';
                $_LANG['node has no images']                    = 'Server heeft geen images';
                $_LANG['node has no templates']                 = 'Server heeft geen templates';
			break;
            
			default: // In case of other language, use English
                $_LANG['node gave no response']                 = 'No response from node';
                $_LANG['node returned error']                   = 'Node returned an error.';
                $_LANG['node returned wrong data']              = 'Node returned incorrect data';
                $_LANG['node has no images']                    = 'Node has no images';
                $_LANG['node has no templates']                 = 'Node has no templates';
			break;
		}
		
		// Save to global array
		global $_module_language_array;
		$_module_language_array['SolusVM'] = $_LANG;
	}
    
    /**
	 * Suspend or unsuspend a VPS on the VPS platform
	 * 
     * @param 	string	   $vps_id         ID of the VPS on the VPS platform
     * @param   string     $action         suspend|unsuspend
	 * @return 	boolean                    True on success; False otherwise.
	 */    
    public function suspend(string $vps_id, string $action)
    {
        if(!$this->validateLogin())
        {
            return FALSE;
        }
        
        switch($action)
        {            
            case 'suspend':
                $response = $this->doCall([
                    'action'    => 'vserver-suspend',
                    'vserverid' => $vps_id,
                ]);
            break;
            
            case 'unsuspend':
                $response = $this->doCall([
                    'action'    => 'vserver-unsuspend',
                    'vserverid' => $vps_id,
                ]);
            break;
        }
        
        /**
    	 * Step 2) provide feedback to HostFact
    	 */
        if($response && $response['status'] == 'success')
        {
            return TRUE;
        }
        else
        {
            $this->__parseError($response['statusmsg']);
            return FALSE; 
        }
    }

    /**
	 * Get details of a VPS by ID
	 * 
     * @param 	string	   $vps_id         ID of the VPS on the VPS platform
	 * @return 	array                      Array with VPS information
	 */     
    public function getVPSDetails(string $vps_id)
    {
        /**
         * Step 1) send get VPS command
         * 
         */
        $vserverinfo = $this->doCall([
            'action'    => 'vserver-info',
            'vserverid' => $vps_id,
        ]);

        if(!$vserverinfo || $info['status'] == 'error') 
        {
            $this->__parseError($info['statusmsg']);
            return FALSE;
        }

        $vserverstatus = $this->doCall([
            'action'    => 'vserver-status',
            'vserverid' => $vps_id,
        ]);

        if(!$vserverstatus || $vserverstatus['status'] == 'error')
        {
            $this->__parseError($vserverstatus['statusmsg']);
            return FALSE;
        }

        /**
    	 * Step 2) provide feedback to HostFact
    	 */
        $vps_details = array();
        $vps_details['status']      = $this->__convertStatus($vserverstatus['statusmsg']);
        $vps_details['hostname']    = $vserverinfo['hostname'];
        $vps_details['ipaddress']   = $vserverinfo['ipaddress']; // not required

        return $vps_details;
    }
    
    /**
	 * Get details of a VPS by hostname
	 * 
     * @param 	string	   $hostname       Hostname of the VPS on the VPS platform
	 * @return 	array      $vps            Array with VPS information
	 */     
    public function getVPSByHostname(string $hostname)
    {
        /**
         * Step 1) send get server command, if there ain't a specific command to get server by hostname, retrieve all servers and loop through them
         * 
         */
        $response = json_decode($this->doCall(['action' => 'node-virtualservers', 'nodeid' => $this->ServerSettings->NodeGroup, 'rdtype' => 'json']), true);

        /**
    	 * Step 2) provide feedback to HostFact
    	 */
        $vps = array();
        if($response && is_array($response['virtualservers']) && count($response['virtualservers']) > 0)
        {
            // loop through servers
            foreach($response['virtualservers'] as $server)
            {
                if($hostname == $server['hostname'])
                {
                    $vserverstatus = $this->doCall([
                        'action'    => 'vserver-status',
                        'vserverid' => $server['vserverid'],
                    ]);
            
                    if(!$vserverstatus || $vserverstatus['status'] == 'error')
                    {
                        $this->__parseError($vserverstatus['statusmsg']);
                        return FALSE;
                    }

                    $vps['id']           = $server['vserverid'];
                    $vps['status']       = $this->__convertStatus($vserverstatus['statusmsg']);
                    break;
                }
            }
        }
        
        if(isset($vps['id']))
        {
            return $vps;
        }
        
        return FALSE;
    }
    
    /**
	 * When a VPS is created from HostFact, this function is regularly called to check it's status
	 * 
     * @param 	string	   $vps_id         ID of the VPS on the VPS platform
	 * @return 	string                     active|building|error; Return status
	 */     
    public function doPending(string $vps_id)
    {
        /**
         * Step 1) send get VPS command (we need the VPS status)
         * 
         */
        $response = $this->doCall([
            'action'    => 'vserver-status',
            'vserverid' => $vps_id,
        ]);

        if(!$response || $response['status'] == 'error')
        {
            $this->__parseError($response['statusmsg']);
            return 'error';
        }
        
        /**
    	 * Step 2) provide feedback to HostFact
         *         based on the VPS status you get from the response, return either active, building or error         
    	 */
        if($response)
        {
            switch($this->__convertStatus($response['statusmsg']))
            {
                case 'active':
                    return 'active';
                break;
                
                case 'building':
                    return 'building';
                break;
                
                default: 
                    return 'error';
                break;
            }
        }
        
        return 'error';
    }
    
    /**
	 * Use this function to convert VPS statusses from the VPS platform to a status HostFact can handle
	 * 
	 * @param 	string	 $status	Status returned from a VPS platform command of a VPS
	 * @return 	string 			    Converted status
	 */    
    private function __convertStatus(string $status)
    {
        switch($status)
        {
            // VPS is active
            case 'online':
                $new_status = 'active';                
            break;
            
            // VPS is rebooting
            //case '':
            //    $new_status = 'reboot';                
            //break;
            
            // VPS is paused
            case 'offline':
                $new_status = 'paused';                
            break;
            
            // VPS has been created and is building
            //case '':
            //    $new_status = 'building';                
            //break;
            
            // VPS error
            //case '':
            //    $new_status = 'error';                
            //break;
            
            default:
                $new_status = '';
            break;
        }       
        
        return $new_status;
    }
}
