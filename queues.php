<?php
require_once('options.php');
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

define("RAW_AGENT_AVAILABLE", 1);
define("RAW_AGENT_RINGING", 6);
define("RAW_AGENT_TALK", 2);
define("RAW_AGENT_UNAVAILABLE", 5);
define("RAW_AGENT_HOLD", 8);
define("RAW_AGENT_PAUSED", 100);

define ("AGENT_AVAILABLE", 1);
define ("AGENT_BUSY", 3);
define ("AGENT_HOLD", 8);
define ("AGENT_PAUSED", 100);
define ("AGENT_NOT_LOGIN", 7);

define ("AGENT_STATE", 'state');
define ("AGENT_STARTTIME", 'start');
define ("AGENT_STATE_DURATION", 'duration');
define ("AGENT_IN", 'in');
define ("AGENT_OUT", 'out');
define ("AGENT_UPTIME", 'uptime');
define ("AGENT_UPCALLS", 'up');
define ("AGENT_ANSWERED_CALLS", 'answered');
define ("AGENT_BOUNCED_CALLS", 'bounced');
define ("AGENT_TRANSFERED_CALLS", 'xfer');
define ("AGENT_AVERAGE_TALK_TIME", 'average');

define ("QUEUES_STATUS_FILE", 'queues.tmp');
define ("QUEUE_VM_FILE", 'vm.tmp');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function is_agent($ext, $a)
{
    $ext_detail = $a->send(new SIPShowPeerAction($ext));
    $raw = $ext_detail->getRawContent();
    $raw_array = explode("\n", $raw);
    
    foreach($raw_array as $item) {
        $pair = explode(":", $item);
        if (trim($pair[0]) == 'Context' && trim($pair[1]) == 'from-internal') {
            return true;
        }
    }

    return false;
}

function agent_belongs($ext)
{
    $queues = array();
    
    $events = get_all_queues_status(get_options());
    foreach($events as $event) {
        if ($event->getName() != 'QueueMember') continue;
        if ($event->getMemberName() == $ext)
            $queues[] = $event->getQueue();
    }

    return $queues;
}

function get_all_agents()
{

    $a = new ClientImpl(get_options());
    $a->open();
    
    $agents = $a->send(new SIPPeersAction());
    $events = $agents->getEvents();
    $agent_names = array();
    
    foreach($events as $event) {
        if ($event->getName() != 'PeerEntry') continue;       

        $ext = $event->getObjectName();        
        if (is_agent($ext, $a))
            $agent_names[] = $ext;
    }    

    $a->close();
    return $agent_names;        
}

function get_all_agents_in_queue($queue)
{
    $status_events = get_all_queues_status(get_options());
    $agents = array();
    
    foreach($status_events as $event) {
        if ($event->getName() == 'QueueMember' && $event->getQueue() == $queue) {
            $agents[] = $event->getMemberName();
        }
    }

    return $agents;
}

function get_all_queues_status()
{    
    $a = new ClientImpl(get_options());
    $a->open();
    
    $queues_status = ($a->send(new QueueStatusAction()));
    $events = $queues_status->getEvents();
        
    $a->close();
    return $events;        
}

function get_all_queues_summary()
{
    $a = new ClientImpl(get_options());
    $a->open();

    $queues_status = ($a->send(new QueueSummaryAction()));
    $events = $queues_status->getEvents();
    
    $a->close();
    return $events;
}

function get_all_queues()
{
    $status_events = get_all_queues_status(get_options());

    foreach($status_events as $event) {
        if ($event->getName() == 'QueueParams') {
            $queues[] = $event->getQueue();
        }
    }

    return $queues;
}

function queue_exist($name)
{
    $all = get_all_queues();

    foreach($all as $n) {
        if ($n == $name) return true;
    }

    return false;
}

function get_vm_from_monitor($name)
{
    if (!file_exists(QUEUE_VM_FILE)) return 0;
    
    $contents = file_get_contents(QUEUE_VM_FILE) or die("Failed to create file QUEUE_VM_FILE");
    $contents_array = explode("\n", $contents);

    foreach($contents_array as $content) {
        $content_array = explode(':', $content);
        if ($content_array[0] == $name)
            return $content_array[1];
    }
    
    return 0;
}

function get_queue_status($name)
{
    $SEMKEY = 1; 
    $sem_id = sem_get($SEMKEY, 1);
    
    sem_acquire($sem_id);

    $status =  internal_get_queue_status($name);

    $contents = file_get_contents(QUEUES_STATUS_FILE) or die("Failed to open file QUEUES_STATUS_FILE");
    $queues_array = explode("\n\n", $contents);
    
    foreach($queues_array as $queue) {
        $agents = explode("\n", $queue);
        $origins = explode(' ',$agents[0]);

        $queue_name = explode('=', $origins[0]);
        if (isset($queue_name[1]))
            $queue_name = $queue_name[1];

        if ($queue_name != $name) continue;

        if (isset($origins[1])) {

            $ins = explode('=',$origins[1]);
            $in = $ins[1];

            $answereds = explode('=',$origins[2]);
            $answered = $answereds[1];
            
            $abandoneds = explode('=',$origins[3]); 
            $abandoned = $abandoneds[1];
            
            $status['inbound_calls'] -= $in;
            $status['answered_calls'] -= $answered;
            $status['abandoned_calls'] -= $abandoned;
            break;
        }
    }   

    sem_release($sem_id);

    return $status;
}

function internal_get_queue_status($name)
{
    if (!queue_exist($name)) return NULL;
    
    $status_events = get_all_queues_status(get_options());
    $summary_events = get_all_queues_summary(get_options());

    foreach ($status_events as $queue_params) {
        if ($queue_params->getName() == "QueueParams")
            if ($queue_params->getQueue() == $name) break;
    }

    foreach($summary_events as $queue_summary) {
        if ($queue_summary->getName() == 'QueueSummary')
            if ($queue_summary->getQueue() == $name) break;
    }

    $status['call_in_queue'] = $queue_params->getCalls();
    $status['longest_waiting_time'] = $queue_summary->getLongestHoldTime();
    $status['agent_available'] = $queue_summary->getAvailable();
    $status['inbound_calls'] = $queue_params->getCompleted() + $queue_params->getAbandoned();
    $status['answered_calls'] = $queue_params->getCompleted();
    $status['average_waiting_time'] = $queue_params->getHoldtime();
    $status['abandoned_calls'] = $queue_params->getAbandoned();
    $status['transferred_vm'] = get_vm_from_monitor($name);

    return $status;
}

function agent_in_queue($queue, $agent)
{
    $status_events = get_all_queues_status(get_options());

    foreach($status_events as $event) {
        if ($event->getName() != 'QueueMember') continue;
        if ($event->getQueue() == $queue && $event->getMemberName() == $agent)  {
            return TRUE;
        }
    }

    return FALSE;
}

function get_agent_init_status($agent)
{
    $status_events = get_all_queues_status(get_options());
    $match_event = NULL;
    $status = 1;
    
    foreach($status_events as $event) {
        if ($event->getName() != 'QueueMember') continue;
        if ($event->getMemberName() == $agent) {
            $match_event = $event;
        }
    }
    
    if ($match_event) {        
        if($match_event->getPaused() == 1) {
            $status = AGENT_PAUSED;
        } else {
            if($match_event->getStatus() == RAW_AGENT_UNAVAILABLE
            || $match_event->getStatus() == RAW_AGENT_TALK
            || $match_event->getStatus() == RAW_AGENT_RINGING){
                $status = AGENT_BUSY;
            }
            else {
                $status = intval($match_event->getStatus());
            }
        }
    }
    else { 
        $status = AGENT_NOT_LOGIN;
    }
    return $status;
}

function get_agent_status_string($a_queue, $exten)
{
    $SEMKEY = 1; 
    $sem_id = sem_get($SEMKEY, 1);
    
    sem_acquire($sem_id);

    $contents = file_get_contents(QUEUES_STATUS_FILE) ;//or die("Failed to open file QUEUES_STATUS_FILE");
    $queues_array = explode("\n\n", $contents);

    foreach($queues_array as $queue) {
        
        $agents = explode("\n", $queue);
        if (count($agents) == 1) continue;
        
        $queue_line = explode(' ',$agents[0]);
	$queue_pair = explode('=',$queue_line[0]);  
        $queue_name =$queue_pair[1];
        
        if ($queue_name != $a_queue) continue;       
        
        foreach($agents as $agent) {
            $agent_status = explode(':', $agent);
            if ($agent_status[0] != $exten) continue;
            sem_release($sem_id);

            return $agent_status[1];
        }
    }
    sem_release($sem_id);

    return NULL;
}

function parse_agent_status($status)
{
    if (!isset($status)) return NULL;
    
    $status_array = explode(' ', $status);
    $resule = array();
    
    foreach ($status_array as $s) {
        $items = explode("=", $s);
        if ($items[0] != NULL)
            $result[$items[0]] = $items[1];
    }

    return $result;
}

function get_agent_status_from_monitor($queue, $agent)
{
    $string = get_agent_status_string($queue, $agent);
    $status = parse_agent_status($string);
    return $status;
}

function get_agent_status($queue, $agent)
{
    $status = array();

    if ($queue == NULL) {
        $status = get_agents_status_total($agent);
        goto out;
    }

    $status = get_agent_status_from_monitor($queue, $agent);

    if ($status[AGENT_STARTTIME] != 0)
        $status[AGENT_STATE_DURATION] = time() - $status[AGENT_STARTTIME];
  out:
    if ($status[AGENT_STATE] == RAW_AGENT_RINGING || $status[AGENT_STATE] == RAW_AGENT_TALK)
        $status[AGENT_STATE] = AGENT_BUSY;
    return $status;
}

function get_agents_status_total($agent)
{
    $queues = get_all_queues();
    $total_status = NULL;;
    
    foreach($queues as $queue) {
        if (!agent_in_queue($queue, $agent)) continue;
        if ($total_status == NULL) {
            $total_status = get_agent_status($queue, $agent);
        }
        else {
            $status = get_agent_status($queue, $agent);
            $total_status[AGENT_IN] += $status[AGENT_IN];
            $total_status[AGENT_ANSWERED_CALLS] += $status[AGENT_ANSWERED_CALLS];
            $total_status[AGENT_BOUNCED_CALLS] += $status[AGENT_BOUNCED_CALLS];

            $total_uptimes = $total_status[AGENT_UPCALLS] + $status[AGENT_UPCALLS];
            if ($total_uptimes != 0) {
                $total_status[AGENT_AVERAGE_TALK_TIME] =
                    intval(($total_status[AGENT_UPCALLS] * $total_status[AGENT_AVERAGE_TALK_TIME] +
                    $status[AGENT_UPCALLS] * $status[AGENT_AVERAGE_TALK_TIME])/$total_uptimes);
                $total_status[AGENT_UPCALLS] = $total_uptimes;
            }
            else {
                $total_status[AGENT_AVERAGE_TALK_TIME] = 0;
            }
        }
    }

    return $total_status;
}

get_agent_status("6000", "4002");
