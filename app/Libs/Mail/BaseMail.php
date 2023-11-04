<?php

namespace App\Libs\Mail;

use App\Models\CmThApplicationDetail;
use App\Models\MailDBQueue;
use DateTime;

class BaseMail
{
    protected $mailDBQueue;

    public function __construct($reservationId)
    {
        $this->mailDBQueue = new MailDBQueue();
        $cmThApplication = CmThApplicationDetail::getApplicationByReservationId($reservationId);
        $this->mailDBQueue->setCmApplicationIdAttribute($cmThApplication->cm_application_id);
        $this->mailDBQueue->setUserIdAttribute($cmThApplication->cmThApplication->user_id);
    }

    protected function setToAddressEnc($toAddressEnc)
    {
        $this->mailDBQueue->setToAddressEncAttribute($toAddressEnc);
    }

    protected function setSubject($subject)
    {
        $this->mailDBQueue->subject = $subject;
    }

    protected function setMessageEnc($messageEnc)
    {
        $this->mailDBQueue->setMessageEncAttribute($messageEnc);
    }

    protected function setNonShowUserFlg($flg = 1)
    {
        $this->mailDBQueue->non_show_user_flg = $flg;
    }

    protected function send($reservationId, $addressFrom)
    {
        $this->mailDBQueue->inQueue($reservationId, $addressFrom);
    }

    protected function getDateOfWeekUsedInMail($datetime)
    {
        $week = ['日', '月', '火', '水', '木', '金', '土'];
        $datetimeObj = new DateTime($datetime);

        return $week[$datetimeObj->format('w')];
    }
}
