<?php
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

class A implements IEventListener
{
    public function handle(EventMessage $event)
    {
        var_dump($event);
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

function get_options()
{
    $options = array(
        'host' => '192.168.10.236',
        'port' => 5038,
        'username' => 'admin',
        'secret' => '18615ae90bd71af63f90664da14b2459',
        'connect_timeout' => 1000,
        'read_timeout' => 1000,
        'scheme' => 'tcp://' // try tls://
    );

    return $options;
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

function get_agent_detail()
{
    $a = new ClientImpl(get_options());
    $a->open();
    
    $detail = ($a->send(new SIPShowPeerAction('4001')));
    $a->close();
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
    $status['transferred_vm'] = 10;

    return $status;
}

function if_agent_login($extension)
{
    $status_events = get_all_queues_status(get_options());

    foreach($status_events as $event) {
        if ($event->getName() != 'QueueMember') continue;
        if ($event->getMemberName() == $extension)
            return TRUE;
    }

    return FALSE;
}

function get_agent_status($extension)
{
    $status_events = get_all_queues_status(get_options());
    $found = FALSE;
    
    foreach($status_events as $event) {
        if ($event->getName() != 'QueueMember') continue;
        if ($event->getMemberName() == $extension) {
            $found = TRUE;
            break; 
        }
    }

    if (!if_agent_login($extension)) {
        $status['state'] = 'Not login';
    }else {
        
    }

    if ($found) {
        $status['start_time'] = '11111';
        $status['duration'] = 'asdfa';
        $status['inbound_calls'] = 100;
        $status['answered_calls'] = $event->getCallsTaken();
        $status['bounced_call'] = 99;
        $status['transferred_call'] =88;
        $status['average_call_duration'] = 100;

    }
}
get_all_queues_name();
get_queue_status('6000');
get_all_agents();
get_agent_detail();
get_agent_status('4001');
if_agent_login('4002');