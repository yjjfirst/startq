<?php
require_once('options.php');
require_once('queues.php');
require(implode(DIRECTORY_SEPARATOR, array(
    __DIR__,
    'vendor',
    'autoload.php'
)));

use PAMI\Client\Impl\ClientImpl;
use PAMI\Listener\IEventListener;
use PAMI\Message\Event\EventMessage;
use PAMI\Message\Action\ListCommandsAction;
use PAMI\Message\Action\ListCategoriesAction;
use PAMI\Message\Action\CoreShowChannelsAction;
use PAMI\Message\Action\CoreSettingsAction;
use PAMI\Message\Action\CoreStatusAction;
use PAMI\Message\Action\StatusAction;
use PAMI\Message\Action\ReloadAction;
use PAMI\Message\Action\CommandAction;
use PAMI\Message\Action\HangupAction;
use PAMI\Message\Action\LogoffAction;
use PAMI\Message\Action\AbsoluteTimeoutAction;
use PAMI\Message\Action\OriginateAction;
use PAMI\Message\Action\BridgeAction;
use PAMI\Message\Action\CreateConfigAction;
use PAMI\Message\Action\GetConfigAction;
use PAMI\Message\Action\GetConfigJSONAction;
use PAMI\Message\Action\AttendedTransferAction;
use PAMI\Message\Action\RedirectAction;
use PAMI\Message\Action\DAHDIShowChannelsAction;
use PAMI\Message\Action\DAHDIHangupAction;
use PAMI\Message\Action\DAHDIRestartAction;
use PAMI\Message\Action\DAHDIDialOffHookAction;
use PAMI\Message\Action\DAHDIDNDOnAction;
use PAMI\Message\Action\DAHDIDNDOffAction;
use PAMI\Message\Action\AgentsAction;
use PAMI\Message\Action\AgentLogoffAction;
use PAMI\Message\Action\MailboxStatusAction;
use PAMI\Message\Action\MailboxCountAction;
use PAMI\Message\Action\VoicemailUsersListAction;
use PAMI\Message\Action\PlayDTMFAction;
use PAMI\Message\Action\DBGetAction;
use PAMI\Message\Action\DBPutAction;
use PAMI\Message\Action\DBDelAction;
use PAMI\Message\Action\DBDelTreeAction;
use PAMI\Message\Action\GetVarAction;
use PAMI\Message\Action\SetVarAction;
use PAMI\Message\Action\PingAction;
use PAMI\Message\Action\ParkedCallsAction;
use PAMI\Message\Action\SIPQualifyPeerAction;
use PAMI\Message\Action\SIPShowPeerAction;
use PAMI\Message\Action\SIPPeersAction;
use PAMI\Message\Action\SIPShowRegistryAction;
use PAMI\Message\Action\SIPNotifyAction;
use PAMI\Message\Action\QueuesAction;
use PAMI\Message\Action\QueueStatusAction;
use PAMI\Message\Action\QueueSummaryAction;
use PAMI\Message\Action\QueuePauseAction;
use PAMI\Message\Action\QueueRemoveAction;
use PAMI\Message\Action\QueueUnpauseAction;
use PAMI\Message\Action\QueueLogAction;
use PAMI\Message\Action\QueuePenaltyAction;
use PAMI\Message\Action\QueueReloadAction;
use PAMI\Message\Action\QueueResetAction;
use PAMI\Message\Action\QueueRuleAction;
use PAMI\Message\Action\MonitorAction;
use PAMI\Message\Action\PauseMonitorAction;
use PAMI\Message\Action\UnpauseMonitorAction;
use PAMI\Message\Action\StopMonitorAction;
use PAMI\Message\Action\ExtensionStateAction;
use PAMI\Message\Action\JabberSendAction;
use PAMI\Message\Action\LocalOptimizeAwayAction;
use PAMI\Message\Action\ModuleCheckAction;
use PAMI\Message\Action\ModuleLoadAction;
use PAMI\Message\Action\ModuleUnloadAction;
use PAMI\Message\Action\ModuleReloadAction;
use PAMI\Message\Action\ShowDialPlanAction;
use PAMI\Message\Action\ParkAction;
use PAMI\Message\Action\MeetmeListAction;
use PAMI\Message\Action\MeetmeMuteAction;
use PAMI\Message\Action\MeetmeUnmuteAction;
use PAMI\Message\Action\EventsAction;
use PAMI\Message\Action\VGMSMSTxAction;
use PAMI\Message\Action\DongleSendSMSAction;
use PAMI\Message\Action\DongleShowDevicesAction;
use PAMI\Message\Action\DongleReloadAction;
use PAMI\Message\Action\DongleStartAction;
use PAMI\Message\Action\DongleRestartAction;
use PAMI\Message\Action\DongleStopAction;
use PAMI\Message\Action\DongleResetAction;
use PAMI\Message\Action\DongleSendUSSDAction;
use PAMI\Message\Action\DongleSendPDUAction;

define("EXT_STATUS_RING", 8);
define("EXT_STATUS_IDLE", 0);
define("EXT_STATUS_CALLING", 1);
define("EXT_HOLD", 16);
define("EXT_CHANNEL_CONNECTED", 6);
define("EXT_NOT_LOGIN", 7);

class Monitor implements IEventListener
{
    private $agents = array();

    function Monitor()
    {
        $agents = get_all_agents();
        foreach($agents as $agent) {
            $this->agents[$agent][AGENT_STATE_KEY] = 0;
	    $this->agents[$agent][AGENT_STARTTIME_KEY] = time();
	    $this->agents[$agent][AGENT_STATE_DURATION_KEY] = 0;
            $this->agents[$agent][AGENT_IN_KEY] = 0;
            $this->agents[$agent][AGENT_OUT_KEY] = 0;
            $this->agents[$agent][AGENT_UPTIME_KEY] = 0;
            $this->agents[$agent][AGENT_UPCALLS_KEY] = 0;
            $this->agents[$agent][AGENT_TRANSFERED_CALLS_KEY] = 0;
            if (!$this->if_agent_login_queue($agent))
                $this->agents[$agent][AGENT_STATE_KEY] = EXT_NOT_LOGIN;
            $this->agents[$agent][AGENT_AVERAGE_TALK_TIME_KEY] = 0;
        }
    }

    public function dump_agents($fd)
    {
        if (!isset($this->agents)) return;
        
        foreach($this->agents as $agent => $status) {
            fwrite($fd, "$agent:");
            foreach($status as $items => $s) {
                fwrite ($fd, "$items=>$s ");
            }
            fwrite($fd, "\n");
        }
    }

    public function save_status()
    {
        $fd = fopen("ext.tmp", "w") or die("Failed to create file:");
        $this->dump_agents($fd);        
    }

    public function get_event_ext($event)
    {
        $name = $event->getName();
        $ext = '';
        
        if ($name == 'Newstate') {
            $channel = $event->getChannel();
            $ext = explode('-', explode('/', $channel)[1])[0];
        } else if ($name == 'ExtensionStatus') {
            $ext = $event->getExtension();
        }

        return $ext;
    }

    private function get_ext_from_channel($channel)
    {
        if (Empty(trim($channel))) return NULL;

        var_dump($channel);
        $ext = explode('-', explode('/', $channel)[1])[0];

        return $ext;
    }
    
    public function count_transfered_call($event)
    {
        $data_array = explode(',', $event->getApplicationData());

        $blind_transfer = explode(':', $data_array[0]);
        $ext = $this->get_ext_from_channel($blind_transfer[1]);

        if ($ext) {
            $this->agents[$ext][AGENT_TRANSFERED_CALLS_KEY] ++;
        }

        $user = explode(':', $data_array[2]);
        if (strstr($event->getChannel(), 'xfer')) {
            $this->agents[trim($user[1])][AGENT_TRANSFERED_CALLS_KEY] ++;
        }
        
    }

    public function handle(EventMessage $event)
    {
        $name = $event->getName();
        $ext = $this->get_event_ext($event);

        if (!empty($ext) && !$this->if_agent_login_queue($ext)) {
            return;
        }

        if ($name == 'Newstate') {
            if ($event->getChannelState() == EXT_CHANNEL_CONNECTED) { 
                $this->agents[$ext][AGENT_UPTIME_KEY] = time();
                $this->agents[$ext][AGENT_UPCALLS_KEY] ++;
            }
            
        } else if ($name == 'ExtensionStatus') {

            //count out calls.
            if ($event->getStatus() == EXT_STATUS_CALLING
            && $this->agents[$ext][AGENT_STATE_KEY] == EXT_STATUS_IDLE) { 
                $this->agents[$ext][AGENT_OUT_KEY] ++;
            }

            //state & state start time.
            if ($this->agents[$ext][AGENT_STATE_KEY] != $event->getStatus()) {
                $this->agents[$ext][AGENT_STARTTIME_KEY] = time();
                $this->agents[$ext][AGENT_STATE_KEY] = $event->getStatus();
            }

            //computer average talk time.
            if ($this->agents[$ext][AGENT_UPTIME_KEY] != 0 && $event->getStatus() == EXT_STATUS_IDLE) {
                $duration = time() - $this->agents[$ext][AGENT_UPTIME_KEY];
                $average = ($this->agents[$ext][AGENT_AVERAGE_TALK_TIME_KEY] * ($this->agents[$ext][AGENT_UPCALLS_KEY] - 1) + $duration)/$this->agents[$ext][AGENT_UPCALLS_KEY];

                $this->agents[$ext][AGENT_AVERAGE_TALK_TIME_KEY] = $average;
            }
            
            if ($event->getStatus() == EXT_STATUS_RING) 
                $this->agents[$ext][AGENT_IN_KEY] ++;
        } else if ($name == 'Newexten' && strstr($event->getApplicationData(), 'Blind Transfer')) {
            $this->count_transfered_call($event);
        } else {
            return;
        }        
        $this->dump_agents(STDIN);
        $this->save_status();
    }

    function if_agent_login_queue($extension)
    {
        $status_events = get_all_queues_status(get_options());
        
        foreach($status_events as $event) {
            if ($event->getName() != 'QueueMember') continue;
            if ($event->getMemberName() == $extension)
                return TRUE;
        }        
        return FALSE;
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

try
{
	$monitor = new ClientImpl(get_options());
	$monitor->registerEventListener(new Monitor());
	$monitor->open();

	$time = time();
	while(true){
        usleep(1000);
	    $monitor->process();
	}
	$monitor->close(); 
} catch (Exception $e) {
	echo $e->getMessage() . "\n";
}
