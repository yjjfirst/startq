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
use PAMI\Message\Event\AgentCalledEvent;
use PAMI\Message\Event\AgentRingNoAnswerEvent;
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
define("EXT_STATUS_HOLD", 16);

function get_queues_vm()
{
    $queues_vm['6000'] = '4001';
    $queues_vm['6001'] = '4002';

    return $queues_vm;
}

class Monitor implements IEventListener
{
    private $queues_vm = array();
    private $queues_status;

    public function __construct()
    {

        $this->queues_status = $this->init_queues_status();


        $this->queues_vm = get_queues_vm();
        foreach($this->queues_vm as $key => $vm) {
            $this->queues_vm[$key] = 0;
        }
            
    }

    public function init_queues_status()
    {
        $queues = get_all_queues();
        $queues_status = array();
        
        foreach($queues as $queue) {
            $queue_status[$queue]  = array();
            $agents_in_queue = get_all_agents_in_queue($queue);
            foreach($agents_in_queue as $agent) {
                $queues_status[$queue][$agent] = array();
                $agent_status = &$queues_status[$queue][$agent];
                $agent_status[AGENT_STATE] = RAW_AGENT_AVAILABLE;
                $agent_status[AGENT_STARTTIME] = time();
                $agent_status[AGENT_STATE_DURATION] = 0;
                $agent_status[AGENT_IN] = 0;
                $agent_status[AGENT_OUT] = 0;
                $agent_status[AGENT_ANSWERED_CALLS] = 0;
                $agent_status[AGENT_BOUNCED_CALLS] = 0;
                $agent_status[AGENT_TRANSFERED_CALLS] = 0;
                $agent_status[AGENT_AVERAGE_TALK_TIME] = 0;
                $agent_status[AGENT_UPTIME] = 0;
                $agent_status[AGENT_UPCALLS] = 0;
                if (!$this->if_agent_login_queue($agent))
                    $agent_status = AGENT_NOT_LOGIN;
            }
            
        }
        return $queues_status;
    }

    public function dump_all_queues($fd)
    {
        $queues = get_all_queues();
        foreach($queues as $queue) {
            $this->dump_one_queue($queue, $fd);
            fwrite($fd, "\n");
        }
    }

    public function dump_one_queue($name, $fd)
    {
        fwrite($fd, "queue=$name\n");
        $this->dump_agents($name, $fd);
    }
    
    public function dump_agents($queue, $fd)
    {
        if (!isset($this->queues_status[$queue])) return;

        $agents = $this->queues_status[$queue];
        foreach($agents as $agent => $status) {
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
        $this->dump_all_queues($fd);        
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

    public function inc_transfered_call($ext)
    {
        $queues = get_all_queues();

        foreach($queues as $queue) {
            $agents_in_queue = get_all_agents_in_queue($queue);            
            foreach($agents_in_queue as $agent) {
                if ($agent != $ext) continue;
                $this->queues_status[$queue][$ext][AGENT_TRANSFERED_CALLS] ++;
            }
            
        }
    }

    public function count_transfered_call($event)
    {
        $data_array = explode(',', $event->getApplicationData());

        $blind_transfer = explode(':', $data_array[0]);
        $ext = $this->get_ext_from_channel($blind_transfer[1]);

        if ($ext) {
            $this->inc_transfered_call($ext);
        }

        $user = explode(':', $data_array[2]);
        if (strstr($event->getChannel(), 'xfer')) {
            $this->inc_transfered_call(trim($user[1]));
        }
        
    }

    public function handle_state_change($event, $ext)
    {
        $queue = $event->getQueue();
        $agent = &$this->queues_status[$queue][$ext];

        if ($event->getStatus() == RAW_AGENT_TALK) {
            if ($agent[AGENT_STATE] == RAW_AGENT_AVAILABLE){
                $agent[AGENT_STARTTIME] = time();
                $agent[AGENT_OUT] ++;
            }
            else if ( $agent[AGENT_STATE] == RAW_AGENT_RINGING) { 
                $agent[AGENT_ANSWERED_CALLS] ++;
            }
        }
        else {
            $agent[AGENT_STARTTIME] = time();
        }

        if ($event->getPause() == 1) {
            $agent[AGENT_STATE] = RAW_AGENT_PAUSED;
            return;
        }
        
        if ($agent[AGENT_STATE] != $event->getStatus()) {
            $agent[AGENT_STATE] = $event->getStatus();
        }
    }

    public function computer_average_talktime($event, $ext)
    {
        $queue = $event->getQueue();
        $agent = &$this->queues_status[$queue][$ext];

        if ($agent[AGENT_UPTIME] != 0) {
            $duration = time() - $agent[AGENT_UPTIME];
            $total =
                $agent[AGENT_AVERAGE_TALK_TIME]
                * ($agent[AGENT_UPCALLS] - 1)
                + $duration;
            $average = $total / $agent[AGENT_UPCALLS];

            $agent[AGENT_UPTIME] = 0;
            $agent[AGENT_AVERAGE_TALK_TIME] = (int)$average;
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
        
        /* if (strstr ($name, "Queue") || strstr($name, "Agent")) */
        /*     var_dump($event); */
        
        if (!empty($ext) && !$this->if_agent_login_queue($ext)) {
            return;
        }

        if ($name == 'ExtensionStatus') {
        }
        else if ($name == 'AgentRingNoAnswer') {
            $queue = $event->getQueue();
            $agent = &$this->queues_status[$queue][$event->getMemberName()];
            $agent[AGENT_BOUNCED_CALLS] ++;                
            $this->dump_all_queues(STDOUT);
        }
        else if ($name == 'AgentConnect') {
            $queue = $event->getQueue();
            $agent = &$this->queues_status[$queue][$event->getMemberName()];
            $agent[AGENT_UPTIME] = time();
            $agent[AGENT_UPCALLS] ++;                
            $this->dump_all_queues(STDOUT);
        }
        else if ($name == 'AgentComplete') {
            $queue = $event->getQueue();
            $agent = &$this->queues_status[$queue][$event->getMemberName()];
            $this->computer_average_talktime($event, $event->getMemberName());
            $this->dump_all_queues(STDOUT);
        }
        else if ($name == 'AgentCalled') {
            $queue = $event->getQueue();
            $agent = &$this->queues_status[$queue][$event->getMemberName()];
            $agent[AGENT_IN] ++;
            $this->dump_all_queues(STDOUT);
        }      
        else if ($name == 'QueueMemberStatus') {
            $this->handle_state_change($event,$ext);
            $this->dump_all_queues(STDOUT);
        }
        else if ($name == 'Newexten') {
            if ($this->possible_transfer($event)) {
                $this->count_transfered_call($event);
            } else if ($event->getApplication() == 'VoiceMail') {
                $this->count_voicemail($event);
            }            
        } 
        else {
            return;
        }
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
