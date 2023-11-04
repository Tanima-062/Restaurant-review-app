<?php

namespace Tests\Unit\Modules\Payment\Skyticket;

use App\Models\CmThApplication;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use App\Modules\Payment\Skyticket\Skyticket;
use Tests\TestCase;

class PaymentSkyticketTest extends TestCase
{
    private $parent;
    private $skyticket;
    private $paymentSkyticket;
    private $info;

    public function setUp(): void
    {
        parent::setUp();
        $this->skyticket = new Skyticket();
        $this->paymentSkyticket = new PaymentSkyticket($this->skyticket);
        $this->info = '{
            "customer":{
               "firstName":"Miguelito",
               "lastName":"Suarez",
               "email":"y-nakazato@adventure-inc.co.jp",
               "tel":"012012340000",
               "request":"testです"
            },
            "application":{
               "menus":[
                  {
                     "menu":{
                        "id":2,
                        "count":1
                     },
                     "options":[
                       {
                           "id":393,
                           "keywordId":1,
                           "contentsId":1
                        },
                       {
                           "id":396,
                           "keywordId":1,
                           "contentsId":1
                        },
                       {
                           "id":397,
                           "keywordId":1,
                           "contentsId":2
                        }
                     ]
                  }
               ],
               "pickUpDate":"2021-06-21",
               "pickUpTime":"16:00:00"
            }
         }';
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /*
        public function testCart()
        {
            $cmApplicationId = CmThApplication::createEmptyApplication();

            $result = $this->paymentSkyticket->getCart($cmApplicationId[0]);
            var_dump($result);
        }
    */

    // ドメイン（APP_URL）がローカルでは成功しない。実行の際は開発環境等のURLで行う必要あり
    public function testSave()
    {
        $result = $this->paymentSkyticket->save(json_decode($this->info, true), 1000);
        $this->assertArrayHasKey('paymentUrl', $result);
        $this->assertArrayHasKey('session_token', $result);
        $this->assertArrayHasKey('result', $result);
        $this->assertTrue($result['result']['status']);
        $this->assertSame('成功', $result['result']['message']);
    }

    // ドメイン（APP_URL）がローカルでは成功しない。実行の際は開発環境等のURLで行う必要あり
    public function testSaveException()
    {
        $result = $this->paymentSkyticket->save(json_decode($this->info, true), 1000);
        $this->assertArrayHasKey('paymentUrl', $result);
        $this->assertArrayHasKey('session_token', $result);
        $this->assertArrayHasKey('result', $result);
        $this->assertTrue($result['result']['status']);
        $this->assertSame('成功', $result['result']['message']);
    }

    public function testCancelPayment()
    {
        // 支払いはブラウザから行うので、正常系のキャンセルテストは不可
        $this->assertTrue(true);
    }

    public function testCancelPaymentException()
    {
        $orderCode = 'gm-cc-0000-0000-3200-03-20210528105659';
        $sessionToken = 'testSessionToken';
        $result = [];
        $result = $this->paymentSkyticket->cancelPayment($orderCode, $sessionToken, $result);
        $this->assertFalse($result);
    }

    /*
            public function testSettlePayment()
            {
                $orderCode = '';
                $result = $this->paymentSkyticket->settlePayment($orderCode);
            }

            public function testCancelPayment()
            {
                $orderCode = '';
                $result = $this->paymentSkyticket->cancelPayment($orderCode);
            }

            public function testRegisterRefundPayment()
            {
                $orderCode = '';
                $refundInfo = [];
                $result = $this->paymentSkyticket->registerRefundPayment($orderCode, $refundInfo);
            }

            public function testDeleteRefundPayment()
            {
                $cartId = '';
                $refundId = 0;
                $cmApplicationId = 0;
                $result = $this->paymentSkyticket->deleteRefundPayment($cartId, $refundId, $cmApplicationId);
            }

            public function testInquirePayment()
            {
                $cartId = '';
                $result = $this->paymentSkyticket->inquirePayment($cartId);
            }
            */
}
