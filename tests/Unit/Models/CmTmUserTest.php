<?php

namespace Tests\Unit\Models;

use App\Libs\Cipher;
use App\Models\CmThApplication;
use App\Models\CmTmUser;
use App\Modules\UserLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CmTmUserTest extends TestCase
{
    private $cmTmUser;
    private $testCmTmUserId;
    private $testCmApplicationId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->cmTmUser = new CmTmUser();
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
        $this->assertSame('グルメ', $this->cmTmUser->getFamilyNamePassportEncAttribute($str));

        // getMiddleNamePassportEncAttribute
        $str = Cipher::encrypt('ミドルネーム');
        $this->assertSame('ミドルネーム', $this->cmTmUser->getMiddleNamePassportEncAttribute($str));

        // getFirstNamePassportEncAttribute
        $str = Cipher::encrypt('太郎');
        $this->assertSame('太郎', $this->cmTmUser->getFirstNamePassportEncAttribute($str));

        // getFamilyNameEncAttribute
        $str = Cipher::encrypt('グルメ');
        $this->assertSame('グルメ', $this->cmTmUser->getFamilyNameEncAttribute($str));

        // getMiddleNameEncAttribute
        $str = Cipher::encrypt('ミドルネーム');
        $this->assertSame('ミドルネーム', $this->cmTmUser->getMiddleNameEncAttribute($str));

        // getFirstNameEncAttribute
        $str = Cipher::encrypt('太郎');
        $this->assertSame('太郎', $this->cmTmUser->getFirstNameEncAttribute($str));

        // getNameEncAttribute
        $str = Cipher::encrypt('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $this->cmTmUser->getNameEncAttribute($str));

        // getTelEncAttribute
        $str = Cipher::encrypt('06123456788');
        $this->assertSame('06123456788', $this->cmTmUser->getTelEncAttribute($str));

        // getFaxEncAttribute
        $str = Cipher::encrypt('06123456789');
        $this->assertSame('06123456789', $this->cmTmUser->getFaxEncAttribute($str));

        // getEmailEncAttribute
        $str = Cipher::encrypt('gourmet-test1@adventure-inc.co.jp');
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $this->cmTmUser->getEmailEncAttribute($str));

        // getSearchEmailEncAttribute
        $str = Cipher::encrypt('gourmet-test2@adventure-inc.co.jp');
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $this->cmTmUser->getSearchEmailEncAttribute($str));

        // getPasswordEncAttribute
        $str = Cipher::encrypt('testpassword123456');
        $this->assertSame('testpassword123456', $this->cmTmUser->getPasswordEncAttribute($str));

        // getPostalCodeEncAttribute
        $str = Cipher::encrypt('150-6024');
        $this->assertSame('150-6024', $this->cmTmUser->getPostalCodeEncAttribute($str));

        // getAddres1EncAttribute
        $str = Cipher::encrypt(' 東京都');
        $this->assertSame(' 東京都', $this->cmTmUser->getAddres1EncAttribute($str));

        // getAddres2EncAttribute
        $str = Cipher::encrypt('渋谷区');
        $this->assertSame('渋谷区', $this->cmTmUser->getAddres2EncAttribute($str));

        // getAddres3EncAttribute
        $str = Cipher::encrypt('恵比寿4-20-3');
        $this->assertSame('恵比寿4-20-3', $this->cmTmUser->getAddres3EncAttribute($str));

        // getAddres4EncAttribute
        $str = Cipher::encrypt('恵比寿ガーデンプレイスタワー 24F');
        $this->assertSame('恵比寿ガーデンプレイスタワー 24F', $this->cmTmUser->getAddres4EncAttribute($str));

        // getCreditCardNoEncAttribute
        $str = Cipher::encrypt('1');
        $this->assertSame('1', $this->cmTmUser->getCreditCardNoEncAttribute($str));

        // getCreditHolderNameEncAttribute
        $str = Cipher::encrypt('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $this->cmTmUser->getCreditHolderNameEncAttribute($str));

        // getCreditExpirationEncAttribute
        $str = Cipher::encrypt('1234567890');
        $this->assertSame('1234567890', $this->cmTmUser->getCreditExpirationEncAttribute($str));

        // getCreditSecurityCdEncAttribute
        $str = Cipher::encrypt('1234');
        $this->assertSame('1234', $this->cmTmUser->getCreditSecurityCdEncAttribute($str));

        // getBankNameEncAttribute
        $str = Cipher::encrypt('グルメミドル太郎1');
        $this->assertSame('グルメミドル太郎1', $this->cmTmUser->getBankNameEncAttribute($str));

        // getBranchBankNameEncAttribute
        $str = Cipher::encrypt('グルメミドル太郎2');
        $this->assertSame('グルメミドル太郎2', $this->cmTmUser->getBranchBankNameEncAttribute($str));

        // getBankAccountTypeEncAttribute
        $str = Cipher::encrypt('1');
        $this->assertSame('1', $this->cmTmUser->getBankAccountTypeEncAttribute($str));

        // getBankAccountNumberEncAttribute
        $str = Cipher::encrypt('1234567890');
        $this->assertSame('1234567890', $this->cmTmUser->getBankAccountNumberEncAttribute($str));

        // getBankAccountNameEncAttribute
        $str = Cipher::encrypt('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $this->cmTmUser->getBankAccountNameEncAttribute($str));

        // getPayerNameEncAttribute
        $str = Cipher::encrypt('グルメ太郎');
        $this->assertSame('グルメ太郎', $this->cmTmUser->getPayerNameEncAttribute($str));
    }

    public function testSetEncAttrubute()
    {
        $this->_createCmThUser();

        $cmTmUser = $this->cmTmUser::find($this->testCmTmUserId);

        // setFamilyNamePassportEncAttribute
        $cmTmUser->setFamilyNamePassportEncAttribute('グルメ');
        $this->assertSame('グルメ', $cmTmUser->family_name_passport_enc);

        // setMiddleNamePassportEncAttribute
        $cmTmUser->setMiddleNamePassportEncAttribute('ミドルネーム');
        $this->assertSame('ミドルネーム', $cmTmUser->middle_name_passport_enc);

        // setFirstNamePassportEncAttribute
        $cmTmUser->setFirstNamePassportEncAttribute('太郎');
        $this->assertSame('太郎', $cmTmUser->first_name_passport_enc);

        // setFamilyNameEncAttribute
        $cmTmUser->setFamilyNameEncAttribute('グルメ');
        $this->assertSame('グルメ', $cmTmUser->family_name_enc);

        // setMiddleNameEncAttribute
        $cmTmUser->setMiddleNameEncAttribute('ミドルネーム');
        $this->assertSame('ミドルネーム', $cmTmUser->middle_name_enc);

        // setFirstNameEncAttribute
        $cmTmUser->setFirstNameEncAttribute('太郎');
        $this->assertSame('太郎', $cmTmUser->first_name_enc);

        // setNameEncAttribute
        $cmTmUser->setNameEncAttribute('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $cmTmUser->name_enc);

        // setTelEncAttribute
        $cmTmUser->setTelEncAttribute('06123456788');
        $this->assertSame('06123456788', $cmTmUser->tel_enc);

        // setFaxEncAttribute
        $cmTmUser->setFaxEncAttribute('06123456789');
        $this->assertSame('06123456789', $cmTmUser->fax_enc);

        // setEmailEncAttribute
        $cmTmUser->setEmailEncAttribute('gourmet-test1@adventure-inc.co.jp');
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $cmTmUser->email_enc);

        // setSearchEmailEncAttribute
        $cmTmUser->setSearchEmailEncAttribute('gourmet-test2@adventure-inc.co.jp');
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $cmTmUser->search_email_enc);

        // setPasswordEncAttribute
        $cmTmUser->setPasswordEncAttribute('testpassword123456');
        $this->assertSame('testpassword123456', $cmTmUser->password_enc);

        // setPostalCodeEncAttribute
        $cmTmUser->setPostalCodeEncAttribute('150-6024');
        $this->assertSame('150-6024', $cmTmUser->postal_code_enc);

        // setAddres1EncAttribute
        $cmTmUser->setAddres1EncAttribute('東京都');
        $this->assertSame('東京都', $cmTmUser->addres1_enc);

        // setAddres2EncAttribute
        $cmTmUser->setAddres2EncAttribute('渋谷区');
        $this->assertSame('渋谷区', $cmTmUser->addres2_enc);

        // setAddres3EncAttribute
        $cmTmUser->setAddres3EncAttribute('恵比寿4-20-3');
        $this->assertSame('恵比寿4-20-3', $cmTmUser->addres3_enc);

        // setAddres4EncAttribute
        $cmTmUser->setAddres4EncAttribute('恵比寿ガーデンプレイスタワー 24F');
        $this->assertSame('恵比寿ガーデンプレイスタワー 24F', $cmTmUser->addres4_enc);

        // setCreditCardTypeIdEncAttribute
        $cmTmUser->setCreditCardTypeIdEncAttribute('1');
        $this->assertSame('1', $cmTmUser->credit_card_type_id_enc);

        // setCreditCardNoEncAttribute
        $cmTmUser->setCreditCardNoEncAttribute('1234567890');
        $this->assertSame('1234567890', $cmTmUser->credit_card_no_enc);

        // setCreditHolderNameEncAttribute
        $cmTmUser->setCreditHolderNameEncAttribute('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $cmTmUser->credit_holder_name_enc);

        // setCreditExpirationEncAttribute
        $cmTmUser->setCreditExpirationEncAttribute('1234567890');
        $this->assertSame('1234567890', $cmTmUser->credit_expiration_enc);

        // setCreditSecurityCdEncAttribute
        $cmTmUser->setCreditSecurityCdEncAttribute('1234');
        $this->assertSame('1234', $cmTmUser->credit_security_cd_enc);

        // setBankNameEncAttribute
        $cmTmUser->setBankNameEncAttribute('グルメミドル太郎1');
        $this->assertSame('グルメミドル太郎1', $cmTmUser->bank_name_enc);

        // setBranchBankNameEncAttribute
        $cmTmUser->setBranchBankNameEncAttribute('グルメミドル太郎2');
        $this->assertSame('グルメミドル太郎2', $cmTmUser->branch_bank_name_enc);

        // setBankAccountTypeEncAttribute
        $cmTmUser->setBankAccountTypeEncAttribute('1');
        $this->assertSame('1', $cmTmUser->bank_account_type_enc);

        // setBankAccountNumberEncAttribute
        $cmTmUser->setBankAccountNumberEncAttribute('1234567890');
        $this->assertSame('1234567890', $cmTmUser->bank_account_number_enc);

        // setBankAccountNameEncAttribute
        $cmTmUser->setBankAccountNameEncAttribute('グルメミドル太郎');
        $this->assertSame('グルメミドル太郎', $cmTmUser->bank_account_name_enc);

        // setPayerNameEncAttribute
        $cmTmUser->setPayerNameEncAttribute('グルメ太郎');
        $this->assertSame('グルメ太郎', $cmTmUser->payer_name_enc);
    }

    public function testCmThApplication()
    {
        $this->_createCmThUser(true);

        $testCmApplicationId = $this->testCmApplicationId;
        $result = $this->cmTmUser::whereHas('cmThApplication', function ($query) use ($testCmApplicationId) {
            $query->where('cm_application_id', $testCmApplicationId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testCmTmUserId, $result[0]['user_id']);
    }

    public function testCreateUserForPayment()
    {
        // 未ログインの場合
        $userId = $this->cmTmUser->createUserForPayment();
        $this->assertTrue(!(is_null($userId)));

        // ログイン済みの場合
        {
            // ログイン実行
            $this->_createCmThUser(true);
            $request = new Request();
            $request->merge([
                'loginId' => 'gourmet-test1@adventure-inc.co.jp',
                'password' =>  'gourmettest123',
            ]);
            $userLoginModules = new UserLogin();
            $result = $userLoginModules->login($request);
            if ($result) {
                // ログイン成功で、ユーザ-IDが取得できることを確認する
                $userId = $this->cmTmUser->createUserForPayment();
                $this->assertSame($this->testCmTmUserId, $userId);
            } else {
                // ログイン失敗した場合、エラーとなるようにする
                $this->assertNull('login error');
            }
            // ログアウト
            $userLoginModules->logout();
        }
    }

    public function testGetMembershipInfo()
    {
        $this->_createCmThUser(true);

        $result = $this->cmTmUser->getMembershipInfo($this->testCmApplicationId);
        $this->assertIsObject($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertSame($this->testCmTmUserId, $result['user_id']);
        $this->assertArrayHasKey('member_status', $result);
        $this->assertSame(1, $result['member_status']);
    }

    private function _createCmThUser($addCmThApplication = false, $email = 'gourmet-test1@adventure-inc.co.jp')
    {
        $cmTmUser = new CmTmUser();
        $cmTmUser->email_enc = $email;
        $cmTmUser->password_enc = hash('sha384', 'gourmettest123');
        $cmTmUser->member_status = 1;
        $cmTmUser->gender_id = 2;
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
