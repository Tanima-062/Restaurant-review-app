<?php

namespace Tests\Unit\Models;

use App\Libs\Cipher;
use App\Models\CmThApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserTest extends TestCase
{
    private $testCmApplicationId;
    private $testCmTmUserId;
    private $user;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->user = new User();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetEncAttribute()
    {
        // 暗号化文字列を用意し、復号結果と同じか比較
        // getFamilyNamePassportEncAttribute
        $str = Cipher::encrypt('グルメ');
        $this->assertSame('グルメ', $this->user->getFamilyNamePassportEncAttribute($str));

        // getMiddleNamePassportEncAttribute
        $str = Cipher::encrypt('ミドルネーム');
        $this->assertSame('ミドルネーム', $this->user->getMiddleNamePassportEncAttribute($str));

        // getFirstNamePassportEncAttribute
        $str = Cipher::encrypt('太郎');
        $this->assertSame('太郎', $this->user->getFirstNamePassportEncAttribute($str));

        // getFamilyNameEncAttribute
        $str = Cipher::encrypt('グルメ');
        $this->assertSame('グルメ', $this->user->getFamilyNameEncAttribute($str));

        // getMiddleNameEncAttribute
        $str = Cipher::encrypt('ミドルネーム');
        $this->assertSame('ミドルネーム', $this->user->getMiddleNameEncAttribute($str));

        // getFirstNameEncAttribute
        $str = Cipher::encrypt('太郎');
        $this->assertSame('太郎', $this->user->getFirstNameEncAttribute($str));

        // getNameEncAttribute
        $str = Cipher::encrypt('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $this->user->getNameEncAttribute($str));

        // getTelEncAttribute
        $str = Cipher::encrypt('06123456788');
        $this->assertSame('06123456788', $this->user->getTelEncAttribute($str));

        // getFaxEncAttribute
        $str = Cipher::encrypt('06123456789');
        $this->assertSame('06123456789', $this->user->getFaxEncAttribute($str));

        // getEmailEncAttribute
        $str = Cipher::encrypt('gourmet-test1@adventure-inc.co.jp');
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $this->user->getEmailEncAttribute($str));

        // getSearchEmailEncAttribute
        $str = Cipher::encrypt('gourmet-test2@adventure-inc.co.jp');
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $this->user->getSearchEmailEncAttribute($str));

        // getPasswordEncAttribute
        $str = Cipher::encrypt('testpassword123456');
        $this->assertSame('testpassword123456', $this->user->getPasswordEncAttribute($str));

        // getPostalCodeEncAttribute
        $str = Cipher::encrypt('150-6024');
        $this->assertSame('150-6024', $this->user->getPostalCodeEncAttribute($str));

        // getAddres1EncAttribute
        $str = Cipher::encrypt(' 東京都');
        $this->assertSame(' 東京都', $this->user->getAddres1EncAttribute($str));

        // getAddres2EncAttribute
        $str = Cipher::encrypt('渋谷区');
        $this->assertSame('渋谷区', $this->user->getAddres2EncAttribute($str));

        // getAddres3EncAttribute
        $str = Cipher::encrypt('恵比寿4-20-3');
        $this->assertSame('恵比寿4-20-3', $this->user->getAddres3EncAttribute($str));

        // getAddres4EncAttribute
        $str = Cipher::encrypt('恵比寿ガーデンプレイスタワー 24F');
        $this->assertSame('恵比寿ガーデンプレイスタワー 24F', $this->user->getAddres4EncAttribute($str));

        // getCreditCardTypeIdEncAttribute
        $str = Cipher::encrypt('jcb');
        $this->assertSame('jcb', $this->user->getCreditCardTypeIdEncAttribute($str));

        // getCreditCardNoEncAttribute
        $str = Cipher::encrypt('1');
        $this->assertSame('1', $this->user->getCreditCardNoEncAttribute($str));

        // getCreditHolderNameEncAttribute
        $str = Cipher::encrypt('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $this->user->getCreditHolderNameEncAttribute($str));

        // getCreditExpirationEncAttribute
        $str = Cipher::encrypt('1234567890');
        $this->assertSame('1234567890', $this->user->getCreditExpirationEncAttribute($str));

        // getCreditSecurityCdEncAttribute
        $str = Cipher::encrypt('1234');
        $this->assertSame('1234', $this->user->getCreditSecurityCdEncAttribute($str));

        // getBankNameEncAttribute
        $str = Cipher::encrypt('グルメミドル太郎1');
        $this->assertSame('グルメミドル太郎1', $this->user->getBankNameEncAttribute($str));

        // getBranchBankNameEncAttribute
        $str = Cipher::encrypt('グルメミドル太郎2');
        $this->assertSame('グルメミドル太郎2', $this->user->getBranchBankNameEncAttribute($str));

        // getBankAccountTypeEncAttribute
        $str = Cipher::encrypt('1');
        $this->assertSame('1', $this->user->getBankAccountTypeEncAttribute($str));

        // getBankAccountNumberEncAttribute
        $str = Cipher::encrypt('1234567890');
        $this->assertSame('1234567890', $this->user->getBankAccountNumberEncAttribute($str));

        // getBankAccountNameEncAttribute
        $str = Cipher::encrypt('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $this->user->getBankAccountNameEncAttribute($str));

        // getPayerNameEncAttribute
        $str = Cipher::encrypt('グルメ太郎');
        $this->assertSame('グルメ太郎', $this->user->getPayerNameEncAttribute($str));
    }

    public function testSetEncAttrubute()
    {
        $this->_createUser();

        $user = $this->user::find($this->testCmTmUserId);

        // setFamilyNamePassportEncAttribute
        $user->setFamilyNamePassportEncAttribute('グルメ');
        $this->assertSame('グルメ', $user->family_name_passport_enc);

        // setMiddleNamePassportEncAttribute
        $user->setMiddleNamePassportEncAttribute('ミドルネーム');
        $this->assertSame('ミドルネーム', $user->middle_name_passport_enc);

        // setFirstNamePassportEncAttribute
        $user->setFirstNamePassportEncAttribute('太郎');
        $this->assertSame('太郎', $user->first_name_passport_enc);

        // setFamilyNameEncAttribute
        $user->setFamilyNameEncAttribute('グルメ');
        $this->assertSame('グルメ', $user->family_name_enc);

        // setMiddleNameEncAttribute
        $user->setMiddleNameEncAttribute('ミドルネーム');
        $this->assertSame('ミドルネーム', $user->middle_name_enc);

        // setFirstNameEncAttribute
        $user->setFirstNameEncAttribute('太郎');
        $this->assertSame('太郎', $user->first_name_enc);

        // setNameEncAttribute
        $user->setNameEncAttribute('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $user->name_enc);

        // setTelEncAttribute
        $user->setTelEncAttribute('06123456788');
        $this->assertSame('06123456788', $user->tel_enc);

        // setFaxEncAttribute
        $user->setFaxEncAttribute('06123456789');
        $this->assertSame('06123456789', $user->fax_enc);

        // setEmailEncAttribute
        $user->setEmailEncAttribute('gourmet-test1@adventure-inc.co.jp');
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $user->email_enc);

        // setSearchEmailEncAttribute
        $user->setSearchEmailEncAttribute('gourmet-test2@adventure-inc.co.jp');
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $user->search_email_enc);

        // setPasswordEncAttribute
        $user->setPasswordEncAttribute('testpassword123456');
        $this->assertSame('testpassword123456', $user->password_enc);

        // setPostalCodeEncAttribute
        $user->setPostalCodeEncAttribute('150-6024');
        $this->assertSame('150-6024', $user->postal_code_enc);

        // setAddres1EncAttribute
        $user->setAddres1EncAttribute('東京都');
        $this->assertSame('東京都', $user->addres1_enc);

        // setAddres2EncAttribute
        $user->setAddres2EncAttribute('渋谷区');
        $this->assertSame('渋谷区', $user->addres2_enc);

        // setAddres3EncAttribute
        $user->setAddres3EncAttribute('恵比寿4-20-3');
        $this->assertSame('恵比寿4-20-3', $user->addres3_enc);

        // setAddres4EncAttribute
        $user->setAddres4EncAttribute('恵比寿ガーデンプレイスタワー 24F');
        $this->assertSame('恵比寿ガーデンプレイスタワー 24F', $user->addres4_enc);

        // setCreditCardTypeIdEncAttribute
        $user->setCreditCardTypeIdEncAttribute('1');
        $this->assertSame('1', $user->credit_card_type_id_enc);

        // setCreditCardNoEncAttribute
        $user->setCreditCardNoEncAttribute('1234567890');
        $this->assertSame('1234567890', $user->credit_card_no_enc);

        // setCreditHolderNameEncAttribute
        $user->setCreditHolderNameEncAttribute('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $user->credit_holder_name_enc);

        // setCreditExpirationEncAttribute
        $user->setCreditExpirationEncAttribute('1234567890');
        $this->assertSame('1234567890', $user->credit_expiration_enc);

        // setCreditSecurityCdEncAttribute
        $user->setCreditSecurityCdEncAttribute('1234');
        $this->assertSame('1234', $user->credit_security_cd_enc);

        // setBankNameEncAttribute
        $user->setBankNameEncAttribute('グルメミドル太郎1');
        $this->assertSame('グルメミドル太郎1', $user->bank_name_enc);

        // setBranchBankNameEncAttribute
        $user->setBranchBankNameEncAttribute('グルメミドル太郎2');
        $this->assertSame('グルメミドル太郎2', $user->branch_bank_name_enc);

        // setBankAccountTypeEncAttribute
        $user->setBankAccountTypeEncAttribute('1');
        $this->assertSame('1', $user->bank_account_type_enc);

        // setBankAccountNumberEncAttribute
        $user->setBankAccountNumberEncAttribute('1234567890');
        $this->assertSame('1234567890', $user->bank_account_number_enc);

        // setBankAccountNameEncAttribute
        $user->setBankAccountNameEncAttribute('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $user->bank_account_name_enc);

        // setPayerNameEncAttribute
        $user->setPayerNameEncAttribute('グルメ太郎');
        $this->assertSame('グルメ太郎', $user->payer_name_enc);
    }

    public function testCmThApplication()
    {
        $this->_createUser(true);

        $testCmApplicationId = $this->testCmApplicationId;
        $result = $this->user::whereHas('cmThApplication', function ($query) use ($testCmApplicationId) {
            $query->where('cm_application_id', $testCmApplicationId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testCmTmUserId, $result[0]['user_id']);
    }

    public function testCreateUserForPayment()
    {
        $userId = $this->user->createUserForPayment();
        $this->assertTrue(!(is_null($userId)));

        // 作成したIDをsessionにセットする
        session()->push('payment.user_id', $userId);
        $userId2 = $this->user->createUserForPayment();
        $this->assertSame($userId, $userId2[0]);
    }

    public function testGetMembershipInfo()
    {
        $this->_createUser(true);

        $result = $this->user->getMembershipInfo($this->testCmApplicationId);
        $this->assertIsObject($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertSame($this->testCmTmUserId, $result['user_id']);
        $this->assertArrayHasKey('member_status', $result);
        $this->assertSame(0, $result['member_status']);
    }

    private function _createUser($addCmThApplication=false)
    {
        $cmTmUser = new User();
        $cmTmUser->save();
        $this->testCmTmUserId = $cmTmUser->user_id;

        if ($addCmThApplication) {
            $CmThApplication = new CmThApplication();
            $CmThApplication->user_id = $this->testCmTmUserId;
            $CmThApplication->save();
            $this->testCmApplicationId = $CmThApplication->cm_application_id;
        }
    }
}
