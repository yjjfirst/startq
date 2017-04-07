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

function get_queues_vm()
{
    $queues_vm['6000'] = '4001';
    $queues_vm['6001'] = '4002';

    return $queues_vm;
}
class Monitor implements IEventListener
{
    private $agents = array();
    private $queues_vm = array();
    
    public function __construct()
    {
        $agents = get_all_agents();
        foreach($agents as $agent) {
            $this->agents[$agent][AGENT_STATE] = 0;
            $this->agents[$agent][AGENT_STARTTIME] = time();
            $this->agents[$agent][AGENT_STATE_DURATION] = 0;
            $this->agents[$agent][AGENT_IN] = 0;
            $this->agents[$agent][AGENT_OUT] = 0;
            $this->agents[$agent][AGENT_ANSWERED_CALLS] = 0;
            $this->agents[$agent][AGENT_BOUNCED_CALLS] = 0;
            $this->agents[$agent][AGENT_TRANSFERED_CALLS] = 0;
            $this->agents[$agent][AGENT_AVERAGE_TALK_TIME] = 0;
            $this->agents[$agent][AGENT_UPTIME] = 0;
            $this->agents[$agent][AGENT_UPCALLS] = 0;
            if (!$this->if_agent_login_queue($agent))
                $this->agents[$agent][AGENT_STATE] = EXT_NOT_LOGIN;
        }

        $this->queues_vm = get_queues_vm();
        foreach($this->queues_vm as $key => $vm) {
            $this->queues_vm[$key] = 0;
        }
            
    }

    public function dump_agents($fd)
    {
        if (!isset($this->agents)) return;
        
        foreach($this->agents as $agent => $status) {
            fwrite($fd, "$agent:");
            foreach($status as $items => $s) {
                fwrite ($fd, "$items=$s ");
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
        } else if ($name == 'QueueMemberStatus') {
            $ext = $event->getMemberName();
        }

        return $ext;
    }

    private function get_ext_from_channel($channel)
    {
        if (Empty(trim($channel))) return NULL;

        $ext = explode('-', explode('/', $channel)[1])[0];

        return $ext;
    }
    
    public function count_transfered_call($event)
    {
        $data_array = explode(',', $event->getApplicationData());

        $blind_transfer = explode(':', $data_array[0]);
        $ext = $this->get_ext_from_channel($blind_transfer[1]);

        if ($ext) {
            $this->agents[$ext][AGENT_TRANSFERED_CALLS] ++;
        }

        $user = explode(':', $data_array[2]);
        if (strstr($event->getChannel(), 'xfer')) {
            $this->agents[trim($user[1])][AGENT_TRANSFERED_CALLS] ++;
        }
        
    }

    public function count_calls($event, $ext)
    {
        if ( $this->agents[$ext][AGENT_STATE] == 6
        && $event->getStatus() == 2) { 
            $this->agents[$ext][AGENT_ANSWERED_CALLS] ++;
        }

        if ( $this->agents[$ext][AGENT_STATE] == 6
        && $event->getStatus() == 1) { 
            $this->agents[$ext][AGENT_BOUNCED_CALLS] ++;
        }
    }

    public function handle_state_change($event, $ext)
    {
        if ($this->agents[$ext][AGENT_STATE] != $event->getStatus()) {
            $this->agents[$ext][AGENT_STARTTIME] = time();
            $this->agents[$ext][AGENT_STATE] = $event->getStatus();
        }

        if ($event->getStatus() == 6)
            $this->agents[$ext][AGENT_IN] ++;
    }

    public function computer_average_talktime($event, $ext)
    {
        if ($this->agents[$ext][AGENT_UPTIME] != 0 && $event->getStatus() == 1) {
            $duration = time() - $this->agents[$ext][AGENT_UPTIME];
            $total =
                $this->agents[$ext][AGENT_AVERAGE_TALK_TIME]
                * ($this->agents[$ext][AGENT_UPCALLS] - 1)
                + $duration;
            $average = $total / $this->agents[$ext][AGENT_UPCALLS];
            
            $this->agents[$ext][AGENT_AVERAGE_TALK_TIME] = (int)$average;
        }
    }

    public function count_voicemail($event)
    {
        $queues_vm = get_queues_vm();
        $fd = fopen(QUEUE_STATUS_FILE, "w") or die("Failed to create file:");

        foreach($queues_vm as $key => $queue_vm) {
            $data = $event->getApplicationData();
            $vm = explode('@', $data)[0];
            if ($queue_vm == $vm) {
                $this->queues_vm["$key"] ++;
            }

            $count = $this->queues_vm["$key"];
            fwrite($fd, "$key:");
            fwrite($fd, "$count");
            fwrite($fd,"\n");
        }

        fclose($fd);
    }

    public function possible_transfer($event)
    {
        return strstr($event->getApplicationData(), 'Blind Transfer');
    }

    public function handle(EventMessage $event)
    {
        $name = $event->getName();
        $ext = $this->get_event_ext($event);

        if (!empty($ext) && !$this->if_agent_login_queue($ext)) {
            return;
        }

        if (strstr($event->getName(), "Queue")) {
        }
        
        if ($name == 'Newstate') {
            if ($event->getChannelState() == EXT_CHANNEL_CONNECTED) { 
                $this->agents[$ext][AGENT_UPTIME] = time();
                $this->agents[$ext][AGENT_UPCALLS] ++;
            }
            
        } else if ($name == 'QueueMemberStatus') {
            var_dump($event);
            $this->count_calls($event, $ext);
            $this->handle_state_change($event,$ext);
            $this->computer_average_talktime($event, $ext);
            
            if ($event->getStatus() == EXT_STATUS_RING) 
                $this->agents[$ext][AGENT_IN] ++;
            $this->dump_agents(STDOUT);
        
        } else if ($name == 'Newexten') {
            if ($this->possible_transfer($event)) {
                $this->count_transfered_call($event);
            } else if ($event->getApplication() == 'VoiceMail') {
                $this->count_voicemail($event);
            }

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
