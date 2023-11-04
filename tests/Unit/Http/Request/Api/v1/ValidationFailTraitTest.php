<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Api\v1\ValidationFailTrait;
use Tests\TestCase;

// traitをテストするため、テスト用クラスを作成
class TestValidationFailTraitClass
{
    use ValidationFailTrait;
}

class ValidationFailTraitTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testMessages()
    {
        $request  = new TestValidationFailTraitClass();
        $result = $request->messages();
        $this->assertCount(6, $result);
        $this->assertArrayHasKey('reservationDate.date_format', $result);
        $this->assertSame(':attributeは「YYYY-MM-DD」の形式で入力してください。', $result['reservationDate.date_format']);
        $this->assertArrayHasKey('visitDate.date_format', $result);
        $this->assertSame(':attributeは「YYYY-MM-DD」の形式で入力してください。', $result['visitDate.date_format']);
        $this->assertArrayHasKey('pickUpDate.date_format', $result);
        $this->assertSame(':attributeは「YYYY-MM-DD」の形式で入力してください。', $result['pickUpDate.date_format']);
        $this->assertArrayHasKey('pickUpTime.date_format', $result);
        $this->assertSame(':attributeは「HH:MM」の形式で入力してください。', $result['pickUpTime.date_format']);
        $this->assertArrayHasKey('application.menus.*.menu.id.required', $result);
        $this->assertSame(':attributeは必ず指定してください。', $result['application.menus.*.menu.id.required']);
        $this->assertArrayHasKey('application.menus.*.menu.count.required', $result);
        $this->assertSame(':attributeは必ず指定してください。', $result['application.menus.*.menu.count.required']);
    }

    public function testAttributes()
    {
        $request  = new TestValidationFailTraitClass();
        $result = $request->attributes();
        $this->assertCount(38, $result);
        $this->assertArrayHasKey('reservationDate', $result);
        $this->assertSame('reservationDate', $result['reservationDate']);
        $this->assertArrayHasKey('reservationId', $result);
        $this->assertSame('reservationId', $result['reservationId']);
        $this->assertArrayHasKey('reservationNo', $result);
        $this->assertSame('reservationNo', $result['reservationNo']);
        $this->assertArrayHasKey('visitDate', $result);
        $this->assertSame('visitDate', $result['visitDate']);
        $this->assertArrayHasKey('visitTime', $result);
        $this->assertSame('visitTime', $result['visitTime']);
        $this->assertArrayHasKey('visitPeople', $result);
        $this->assertSame('visitPeople', $result['visitPeople']);
        $this->assertArrayHasKey('loginId', $result);
        $this->assertSame('loginId', $result['loginId']);
        $this->assertArrayHasKey('menuId', $result);
        $this->assertSame('menuId', $result['menuId']);
        $this->assertArrayHasKey('menuIds', $result);
        $this->assertSame('menuIds', $result['menuIds']);
        $this->assertArrayHasKey('evaluationCd', $result);
        $this->assertSame('evaluationCd', $result['evaluationCd']);
        $this->assertArrayHasKey('isRealName', $result);
        $this->assertSame('isRealName', $result['isRealName']);
        $this->assertArrayHasKey('userName', $result);
        $this->assertSame('userName', $result['userName']);
        $this->assertArrayHasKey('isRemember', $result);
        $this->assertSame('isRemember', $result['isRemember']);
        $this->assertArrayHasKey('rememberToken', $result);
        $this->assertSame('rememberToken', $result['rememberToken']);
        $this->assertArrayHasKey('pickUpDate', $result);
        $this->assertSame('pickUpDate', $result['pickUpDate']);
        $this->assertArrayHasKey('pickUpTime', $result);
        $this->assertSame('pickUpTime', $result['pickUpTime']);
        $this->assertArrayHasKey('cookingGenreCd', $result);
        $this->assertSame('cookingGenreCd', $result['cookingGenreCd']);
        $this->assertArrayHasKey('menuGenreCd', $result);
        $this->assertSame('menuGenreCd', $result['menuGenreCd']);
        $this->assertArrayHasKey('suggestCd', $result);
        $this->assertSame('suggestCd', $result['suggestCd']);
        $this->assertArrayHasKey('suggestText', $result);
        $this->assertSame('suggestText', $result['suggestText']);
        $this->assertArrayHasKey('appCd', $result);
        $this->assertSame('appCd', $result['appCd']);
        $this->assertArrayHasKey('lowerPrice', $result);
        $this->assertSame('lowerPrice', $result['lowerPrice']);
        $this->assertArrayHasKey('upperPrice', $result);
        $this->assertSame('upperPrice', $result['upperPrice']);
        $this->assertArrayHasKey('sessionToken', $result);
        $this->assertSame('sessionToken', $result['sessionToken']);
        $this->assertArrayHasKey('cd3secResFlg', $result);
        $this->assertSame('cd3secResFlg', $result['cd3secResFlg']);
        $this->assertArrayHasKey('customer.firstName', $result);
        $this->assertSame('customer.firstName', $result['customer.firstName']);
        $this->assertArrayHasKey('customer.lastName', $result);
        $this->assertSame('customer.lastName', $result['customer.lastName']);
        $this->assertArrayHasKey('customer.email', $result);
        $this->assertSame('customer.email', $result['customer.email']);
        $this->assertArrayHasKey('customer.tel', $result);
        $this->assertSame('customer.tel', $result['customer.tel']);
        $this->assertArrayHasKey('customer.request', $result);
        $this->assertSame('customer.request', $result['customer.request']);
        $this->assertArrayHasKey('application.menus.*.menu.id', $result);
        $this->assertSame('application.menus.*.menu.id', $result['application.menus.*.menu.id']);
        $this->assertArrayHasKey('application.menus.*.menu.count', $result);
        $this->assertSame('application.menus.*.menu.count', $result['application.menus.*.menu.count']);
        $this->assertArrayHasKey('application.menus.*.options.*.id', $result);
        $this->assertSame('application.menus.*.options.*.id', $result['application.menus.*.options.*.id']);
        $this->assertArrayHasKey('application.menus.*.options.*.keywordId', $result);
        $this->assertSame('application.menus.*.options.*.keywordId', $result['application.menus.*.options.*.keywordId']);
        $this->assertArrayHasKey('application.menus.*.options.*.contentsId', $result);
        $this->assertSame('application.menus.*.options.*.contentsId', $result['application.menus.*.options.*.contentsId']);
        $this->assertArrayHasKey('application.pickUpDate', $result);
        $this->assertSame('application.pickUpDate', $result['application.pickUpDate']);
        $this->assertArrayHasKey('application.pickUpTime', $result);
        $this->assertSame('application.pickUpTime', $result['application.pickUpTime']);
        $this->assertArrayHasKey('payment.returnUrl', $result);
        $this->assertSame('payment.returnUrl', $result['payment.returnUrl']);
    }
}
