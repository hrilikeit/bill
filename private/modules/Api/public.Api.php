<?php

require '../private/views/WebShowView.php';
require '../private/modules/Purchase/public.Purchase.php';
require '../private/domain/class.StartLiveMeetingData.php';
//require '../private/domain/class.Entertainer.php';

class Api extends StaysailPublic
{
    protected $framework;

    public function __construct($dbc = '')
    {
        $this->framework = StaysailIO::engage();
    }

    public function getHTML()
    {
        $job = StaysailIO::get('job');

        switch ($job) {
            case 'get_paginated_messages':
                $data = $this->getPaginatedMessages();
                break;

            case 'start_livestreaming_meeting':
                $data = $this->startLivestreamingMeeting();
                break;
            case 'Entertainer_tips':
                $data = $this->getEntertainerTips();
                break;
            case 'Entertainer_goal':
                $data = $this->getEntertainerGoal();
                break;
            case 'get_Live_Like_Count':
                $data = $this->getLiveLikeCount();
                break;
            case 'add_Live_Like_Count':
                $data = $this->addLiveLikeCount();
                break;
            case 'cancellation_Live_Like_Count':
                $data = $this->cancellationLiveLikeCount();
                break;
            case 'live_tip':
                $data = $this->liveTip();
                break;
            case 'add_thumbnail':
                $data = $this->addThumbnail();
                break;
            case 'get_goal_tip_user':
                $data = $this->getGoalTipUser();
                break;
            case 'get_all_models':
                $data = $this->getAllModels();
                break;
            case 'get_posts':
                $data = $this->getPosts();
                break;
            default:
                $data = ['error' => 'Not Found'];
        }
        echo json_encode($data);
        die;
    }

    
    /**
     * Messages inbox pagination endpoint.
     *
     * GET params:
     *   - type: unread | read | sent
     *   - page: 1..n
     *
     * Response:
     *   {
     *     pagination: { page, per_page, total_items, total_pages },
     *     messages: [ { id, link, name, avatar, from, to, send_time, is_read } ]
     *   }
     */
    private function getPaginatedMessages()
    {
        $member_id = StaysailIO::session('Member.id');
        if (!$member_id && isset($_SESSION['Member.id'])) {
            $member_id = $_SESSION['Member.id'];
        }

        if (!$member_id) {
            if (function_exists('http_response_code')) {
                http_response_code(401);
            }
            return ['error' => 'Unauthorized'];
        }

        $type = strtolower(trim((string) StaysailIO::get('type')));
        $page = (int) StaysailIO::get('page');
        if ($page < 1) {
            $page = 1;
        }

        // Keep the page size modest for faster responses.
        $per_page = 10;

        $Member = new Member($member_id);

        // Fetch messages for the requested type.
        $messages = [];
        if ($type === 'sent') {
            $messages = $Member->getSentMessages();
        } else {
            // Both "read" and "unread" are based on inbound messages.
            $all = $Member->getMessages();
            foreach ($all as $Private_Message) {
                $is_read = (bool) $Private_Message->receive_time;
                if ($type === 'read' && $is_read) {
                    $messages[] = $Private_Message;
                } elseif (($type === 'unread' || $type === '') && !$is_read) {
                    $messages[] = $Private_Message;
                }
            }
            if ($type !== 'read' && $type !== 'unread') {
                // Default to unread when the type is missing/invalid.
                $type = 'unread';
            }
        }

        $total_items = is_array($messages) ? count($messages) : 0;
        $total_pages = $total_items ? (int) ceil($total_items / $per_page) : 0;
        if ($total_pages && $page > $total_pages) {
            $page = $total_pages;
        }

        $offset = ($page - 1) * $per_page;
        $page_messages = is_array($messages) ? array_slice($messages, $offset, $per_page) : [];

        $out_messages = [];
        foreach ($page_messages as $Private_Message) {
            if (!$Private_Message || !isset($Private_Message->id)) {
                continue;
            }

            $is_read = (bool) $Private_Message->receive_time;
            $subject = (string) $Private_Message->name;

            // Format matches the existing UI (see Private_Message::getSelectorHTML).
            $send_time = '';
            if (!empty($Private_Message->send_time)) {
                $send_time = date('m/d/y h:ia', strtotime($Private_Message->send_time));
            }

            $link = "?mode=Message&job=read&id={$Private_Message->id}";

            $from = '';
            $to = '';
            $avatar = '';

            if ($type === 'sent') {
                $recipient = $Private_Message->getToMember();
                if ($recipient && !empty($recipient->id)) {
                    $to = (string) $recipient->name;
                    $avatar = $this->getMemberAvatarURLSafe($recipient);
                } else {
                    $to = '???';
                    $avatar = '/avatar.php?a=0';
                }
            } else {
                // Unread / Read (inbound)
                if (!empty($Private_Message->from_Admin_id)) {
                    $from = 'LSF Admin';
                    $avatar = '/avatar.php?a=0';
                } else {
                    $sender = $Private_Message->getFromMember();
                    if ($sender && !empty($sender->id)) {
                        $from = (string) $sender->name;
                        $avatar = $this->getMemberAvatarURLSafe($sender);
                    } else {
                        $from = '???';
                        $avatar = '/avatar.php?a=0';
                    }
                }
            }

            $out_messages[] = [
                'id' => (int) $Private_Message->id,
                'link' => $link,
                'name' => $subject,
                'avatar' => $avatar,
                'from' => $from,
                'to' => $to,
                'send_time' => $send_time,
                'is_read' => $is_read,
            ];
        }

        return [
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_items,
                'total_pages' => $total_pages,
            ],
            'messages' => $out_messages,
        ];
    }

    /**
     * Returns a safe avatar URL for a member.
     * Uses the entertainer avatar if available, otherwise falls back to /avatar.php.
     */
    private function getMemberAvatarURLSafe(Member $Member)
    {
        // Prefer entertainer avatar if one exists.
        $Entertainer = $Member->getAccountOfType('Entertainer');
        if ($Entertainer && method_exists($Member, 'hasEntertainerAvatar') && $Member->hasEntertainerAvatar()) {
            return $Member->getEntertainerAvatarURL();
        }
        return $Member->getAvatarURL();
    }


private function startLivestreamingMeeting()
    {
        $meetingId = StaysailIO::get('meeting_id');
        //$MemberId = StaysailIO::get('Member_id');
        $MemberId = $_SESSION['Member.id'];
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        //$parameters = "{\n  \"video_config\": {\n    \"height\" : 1080,\n    \"width\" : 1920\n  }\n  \n}";
        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)))
        {
           //$parameters = "{\n  \"video_config\": {\n    \"height\" : 1920,\n    \"width\" : 1080\n  }\n  \n}";
        }
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.dyte.io/v2/meetings/$meetingId/livestreams",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
           // CURLOPT_POSTFIELDS => $parameters,
            CURLOPT_POSTFIELDS =>"",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic ZTQ4ZWJhOGUtMDJlZS00ZTNmLWFkN2YtZTFmZTQ5MjhhYTI1OjA3OWQ0ZjMyZTgzNDQyODQ0Y2Ey",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        var_dump($response);
        $err = curl_error($curl);
        curl_close($curl);


        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $response = json_decode($response, true);
            $response = $response['data'];
            $startLiveMeetingData = new StartLiveMeetingData();
            $startLiveMeetingData->Member_id = $MemberId;
            $startLiveMeetingData->ingest_server = $response['ingest_server'];
            $startLiveMeetingData->livestream_id = $response['id'];
            $startLiveMeetingData->stream_key = $response['stream_key'];
            $startLiveMeetingData->playback_url = $response['playback_url'];
            $startLiveMeetingData->save();

            //echo 1;

            return $response;
        }
    }

    private function getEntertainerTips()
    {
        $entertainerId = StaysailIO::get('Entertainer_id');
        $data = [];
        if ($entertainerId) {
            $TipMenuIds = $this->framework->getAllIdsRowsByField('TipMenu',
                'Entertainer_id', $entertainerId);
            foreach ($TipMenuIds as $TipMenuId) {
                $TipMenu = new TipMenu($TipMenuId[0]);
                $data[] = [
                    'name' => $TipMenu->name,
                    'price' => $TipMenu->price
                ];
            }
        }

        return $data;
    }

    private function getEntertainerGoal()
    {
        $entertainerId = StaysailIO::get('Entertainer_id');
        $data = [];
        if ($entertainerId) {
            $filters = array(new Filter(Filter::Match, array('Entertainer_id' => $entertainerId)),
                new Filter(Filter::Sort, 'id DESC'),
            );
            $goalHistory = $this->framework->getSingle('Goal', $filters);

            if($goalHistory && isset($goalHistory->id)){
                $data[] = [
                    'id' => $goalHistory->id,
                    'name' => $goalHistory->name,
                    'price' => $goalHistory->price,
                    'current_count' => $goalHistory->current_count,
                    'status' => $goalHistory->status,
                    'percent ' => intval($goalHistory->price)*intval($goalHistory->current_count)/100
                ];
            }else{
                $data[] = [
                    'id' =>1,
                    'name' => "name",
                    'price' => 999,
                    'current_count' => 999,
                    'status' => 1,
                    'percent ' => 1
                ];
            }

        }
        return $data;
    }

//    private function getEntertainerGoal()
//    {
//        $entertainerId = StaysailIO::get('Entertainer_id');
//        $data = [];
//
//        if ($entertainerId) {
//            $filters = [
//                new Filter(Filter::Match, ['Entertainer_id' => $entertainerId]),
//                new Filter(Filter::Sort, 'id DESC')
//            ];
//            $goalHistory = $this->framework->getSingle('Goal', $filters);
//
//            if ($goalHistory && isset($goalHistory->id)) {
//                $data[] = [
//                    'id' => $goalHistory->id,
//                    'name' => isset($goalHistory->name) ? $goalHistory->name : null,
//                    'price' => isset($goalHistory->price) ? $goalHistory->price : null,
//                    'current_count' => isset($goalHistory->current_count) ? $goalHistory->current_count : null,
//                    'status' => isset($goalHistory->status) ? $goalHistory->status : null,
//                    'percent' => isset($goalHistory->price) && isset($goalHistory->current_count) ?
//                        intval($goalHistory->price) * intval($goalHistory->current_count) / 100 :
//                        0
//                ];
//            } else {
//                $data[] = "no goal";
//            }
//        } else {
//            $data[] = "no entertainer";
//        }
//
//        return $data;
//    }

    private function getLiveLikeCount()
    {
        $WebShowDyteId = StaysailIO::get('Web_Show_Dyte_id');
        $webShowDyte = new WebShowDyte($WebShowDyteId);

        return (int)$webShowDyte->like_count;
    }

    private function addLiveLikeCount()
    {
        $WebShowDyteId = StaysailIO::get('Web_Show_Dyte_id');
        $webShowDyte = new WebShowDyte($WebShowDyteId);
        $webShowDyte->like_count ++;
        $webShowDyte->save();

        return $webShowDyte->like_count;
    }

    private function cancellationLiveLikeCount()
    {
        $WebShowDyteId = StaysailIO::get('Web_Show_Dyte_id');
        $webShowDyte = new WebShowDyte($WebShowDyteId);
        $webShowDyte->like_count = 0;
        $webShowDyte->save();

        return $webShowDyte->like_count;
    }

    public function liveTip()
    {
        $amount = $_POST['amount'];
//        $FanId = $_POST['Member_id'];
        $FanId = $_SESSION['Member.id'];

       // $FanId = isset($_POST['Member_id']) ? $_POST['Member_id'] : (isset($_SESSION['Member.id']) ? $_SESSION['Member.id'] : null);

        //$goalId = null;
//        if (isset($_POST['goal_id'])){
//            $goalId = $_POST['goal_id'];
//        }
        $filter = array(
            new Filter(Filter::Match, array('Entertainer_id' => $_POST['Entertainer_id']))
        );
        $goalIdFromTable = $this->framework->getSingle('Goal', $filter);

            $idGoal = $goalIdFromTable->id;

        $data = [];
        $Member = new Member($FanId);
        $Purchase = new Purchase();
        $filter = array(
            new Filter(Filter::Match, array('Member_id' => $FanId)),
            new Filter(Filter::Match, array('default_card' => 1))
        );

        $pm = $this->framework->getSingle('Payment_Method', $filter);


        $amount = intval($amount);
        if ($amount > 0) {
            $Order = new Order();
          //  $Order->goal_id = $goalId;
            $Order->goal_id = $idGoal;
            $Order->setMember($Member);
            $Order->addOrderLine('Fan goal', $amount, 1, $Member);
            //list ($ok, $message,  $responseText) = $Purchase->validateCharge($Order, '', $pm);
            list ($ok, $message, $responseText) = $Purchase->validateCharge($Order, '', $pm, true);
//           var_dump($ok);
//           var_dump($message);
//           var_dump($responseText);
           // $ok = true;
//            var_dump($ok);
//            var_dump($message);
//            var_dump($responseText);

            //$responseText = "Approved";
            if ($ok) {
               // $goal = new Goal($goalId);
                $goal = new Goal($idGoal);
                $goal->current_count = 1;
                if ($goal->status == 0 ){
                    $goal->current_count = (int)$goal->current_count + $amount;
                    if ($goal->price <= $goal->current_count){
                        $goal->status = 1;
                    }
                    $goal->save();
                }
                $message = ['status' => "Success", 'response_text' => $responseText];
              //  $message = "Success";
            } else {
                $Order->cancel();
               // $message = "Cancel";
                $message = ['status' => "Cancel", 'response_text' => $responseText];
            }
        } else {
            $message = "Amount is zero";
        }

        return $message;
    }

    public function addThumbnail()
    {
        $meetingId = $_POST['meeting_id'];
        $thumb = $_POST['thumb'];
       // $viewCount = $_POST['view_count'];
        $filter = array(
            new Filter(Filter::Match, array('meeting_id' => $meetingId))
        );
        $WebShowDyte = $this->framework->getSingle('WebShowDyte', $filter);
        $WebShowDyte->thumbnail = $thumb;
        //$WebShowDyte->view_count = $viewCount;
        $WebShowDyte->view_count = 2;
        $WebShowDyte->save();

        return 1;
    }

    public function getGoalTipUser()
    {
        $goalId = StaysailIO::post('goal_id');
        $memberId = StaysailIO::post('Member_id');

        $filter = array(
            new Filter(Filter::Match, array('goal_id' => $goalId, 'Member_id' => $memberId))
        );
        $order = $this->framework->getSingle('Order', $filter);

        if (!$order){
            return false;
        }

        return true;
    }

    private function getAllModels()
    {
        $page = (int) $_REQUEST['page'];
        $limit = 48;
        $offset = ($page * $limit) - $limit;
        $member_id = $_REQUEST['memberId'];
        $Member = new Member($member_id);
        $Fan = $Member->getAccountOfType('Fan');
        $subscriptions =  $Fan->getActiveSubscriptions();
        $subscribedEntertainers = [];

        foreach ($subscriptions as $s) {
            $subscribedEntertainers[] = $s->Entertainer_id;
        }


        $entertainers = $Fan->getActiveEntertainers();

        $data = [];
        if (sizeof($entertainers)) {
            foreach ($entertainers as $entertainer)
            {
                if (!in_array($entertainer->id, $subscribedEntertainers)) {
                    $entertainerData['id'] = $entertainer->id;
                    $entertainerData['member_id'] = $entertainer->Member_id;
                    $entertainerData['name'] = $entertainer->name;
                    $data[] = $entertainerData;
                }
            }
        }

        $pageData = array_slice($data, $offset, $limit);

        $pages = count($data) / $limit;
        $pages = (int) round($pages, 0, PHP_ROUND_HALF_UP);

        return json_encode(["data" => $pageData, "page" => $page, 'pages' => $pages]);
    }

    private function getPosts()
    {
        $page = (int) $_REQUEST['page'];
        $limit = 10;
        $offset = ($page * $limit) - $limit;
        $entertainer_id = $_REQUEST['entertainerId'];
        $Entertainer = new Entertainer($entertainer_id);

        $pagesCount = $Entertainer->getPostsCount();
        $pages = $pagesCount / $limit;
        $pages = (int) round($pages, 0, PHP_ROUND_HALF_UP);
        $posts = $Entertainer->getPostsPagination($offset, $limit);

        $html = '';
        foreach ($posts as $Post) {
            $html .= $Post->getHTML();
        }

        header('Content-Type: application/json');
        return ['data' => $html, "page" => $page, 'pages' => $pages];
    }
}
