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

define ("AGENT_HOLD", 1);
define ("AGENT_AVAILABLE", 2);
define ("AGENT_BUSY", 3);
define ("AGENT_PAUSED", 4);
define ("AGENT_NOT_LOGIN", 5);

define ("AGENT_STATE_KEY", 'state');
define ("AGENT_STARTTIME_KEY", 'starttime');
define ("AGENT_STATE_DURATION_KEY", 'duration');
define ("AGENT_IN_KEY", 'in');
define ("AGENT_OUT_KEY", 'out');
define ("AGENT_UPTIME_KEY", 'uptime');
define ("AGENT_UPCALLS_KEY", 'upcalls');
define ("AGENT_ANSWERED_CALLS_KEY", 'answered_calls');
define ("AGENT_BOUNCED_CALLS_KEY", 'bounced_calls');
define ("AGENT_TRANSFERED_CALLS_KEY", 'transfered');
define ("AGENT_AVERAGE_TALK_TIME_KEY", 'average');

define ("EXT_STATUS_FILE", 'ext.tmp');
define ("QUEUE_STATUS_FILE", 'queue.tmp');

class A implements IEventListener
{
    public function handle(EventMessage $event)
    {
        var_dump($event);
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

function get_all_queues_status()
{    
    $a = new ClientImpl(get_options());
    $a->open();
    
    $queues_status = ($a->send(new QueueStatusAction()));
    $events = $queues_status->getEvents();
        
    $a->close();
    return $events;        
}

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

function get_all_queues_summary()
{
    $a = new ClientImpl(get_options());
    $a->open();

    $queues_status = ($a->send(new QueueSummaryAction()));
    $events = $queues_status->getEvents();
        
    $a->close();
    return $events;
}

function get_all_queues_name()
{
    $status_events = get_all_queues_status(get_options());

    foreach($status_events as $event) {
        if ($event->getName() == 'QueueParams') {
            $queues[] = $event->getQueue();
        }
    }

    return $queues;
}

function get_vm_from_monitor($name)
{
   $contents = file_get_contents(QUEUE_STATUS_FILE);
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
    $status_events = get_all_queues_status(get_options());
    $summary_events = get_all_queues_summary(get_options());

    foreach ($status_events as $queue_params) {
        if ($queue_params->getQueue() == $name) break;
    }

    foreach($summary_events as $queue_summary) {
        if ($queue_summary->getQueue() == $name) break;
    }

    $status['call_in_queue'] = $queue_params->getCalls();
    $status['longest_waiting_time'] = $queue_summary->getLongestHoldTime();
    $status['agent_available'] = $queue_summary->getAvailable();
    $status['inbould_calls'] = $queue_params->getCompleted() + $queue_params->getAbandoned();
    $status['answered_calls'] = $queue_params->getCompleted();
    $status['average_waiting_time'] = $queue_params->getHoldtime();
    $status['abandoned_call'] = $queue_params->getAbandoned();
    $status['transferred_vm'] = get_vm_from_monitor();

    return $status;
}

function get_agent_status_from_queues($extension, $status)
{
    $status_events = get_all_queues_status(get_options());
    $found = FALSE;

    foreach($status_events as $event) {
        if ($event->getName() != 'QueueMember') continue;
        if ($event->getMemberName() == $extension) {
            $status[AGENT_ANSWERED_CALLS_KEY] += $event->getCallsTaken();
            $found = TRUE;
        }
    }

    if (!$found) { 
        $status[AGENT_ANSWERED_CALLS_KEY] = 0;
    }
    return $status;
}

function get_agent_status_string($exten)
{
   $contents = file_get_contents(EXT_STATUS_FILE);
   $contents_array = explode("\n", $contents);

   foreach($contents_array as $content) {
       $content_array = explode(':', $content);
       if ($content_array[0] == $exten)
           return $content_array[1];
   }
}

function parse_agent_status($status)
{
    $status_array = explode(' ', $status);

    foreach ($status_array as $s) {
        $items = explode("=>", $s);
        if ($items[0] != NULL)
            $result[$items[0]] = $items[1];
    }

    return $result;
}

function get_agent_status_from_monitor($agent)
{
    $string = get_agent_status_string($agent);
    $status = parse_agent_status($string);
    return $status;
}

function get_agent_status($agent)
{
    $status = get_agent_status_from_monitor($agent);
    $status = get_agent_status_from_queues($agent, $status);

    if ($status[AGENT_STARTTIME_KEY] != 0)
        $status[AGENT_STATE_DURATION_KEY] = time() - $status[AGENT_STARTTIME_KEY];

    $status[AGENT_BOUNCED_CALLS_KEY] = $status[AGENT_IN_KEY] -$status[AGENT_ANSWERED_CALLS_KEY];
    return $status;
}
