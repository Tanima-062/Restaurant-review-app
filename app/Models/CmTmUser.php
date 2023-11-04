<?php

namespace App\Models;

use App\Libs\Cipher;
use App\Modules\UserLogin;
use Illuminate\Database\Eloquent\Model;

class CmTmUser extends Model
{
    protected $table = 'common.cm_tm_user';
    protected $primaryKey = 'user_id';
    protected $guarded = ['user_id'];
    public $timestamps = false;

    //*******************************************************
    // get時に復号化する
    //*******************************************************

    public function getFamilyNamePassportEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getMiddleNamePassportEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getFirstNamePassportEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getFamilyNameEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getMiddleNameEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getFirstNameEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getNameEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getTelEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getFaxEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getEmailEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getSearchEmailEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getPasswordEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getPostalCodeEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getAddres1EncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getAddres2EncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getAddres3EncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getAddres4EncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getCreditCardTypeIdEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getCreditCardNoEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getCreditHolderNameEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getCreditExpirationEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getCreditSecurityCdEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getBankNameEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getBranchBankNameEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getBankAccountTypeEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getBankAccountNumberEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getBankAccountNameEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    public function getPayerNameEncAttribute($value)
    {
        return Cipher::decrypt($value);
    }

    //*******************************************************
    // set時に暗号化する
    //*******************************************************

    public function setFamilyNamePassportEncAttribute($value)
    {
        $this->attributes['family_name_passport_enc'] = Cipher::encrypt($value);
    }

    public function setMiddleNamePassportEncAttribute($value)
    {
        $this->attributes['middle_name_passport_enc'] = Cipher::encrypt($value);
    }

    public function setFirstNamePassportEncAttribute($value)
    {
        $this->attributes['first_name_passport_enc'] = Cipher::encrypt($value);
    }

    public function setFamilyNameEncAttribute($value)
    {
        $this->attributes['family_name_enc'] = Cipher::encrypt($value);
    }

    public function setMiddleNameEncAttribute($value)
    {
        $this->attributes['middle_name_enc'] = Cipher::encrypt($value);
    }

    public function setFirstNameEncAttribute($value)
    {
        $this->attributes['first_name_enc'] = Cipher::encrypt($value);
    }

    public function setNameEncAttribute($value)
    {
        $this->attributes['name_enc'] = Cipher::encrypt($value);
    }

    public function setTelEncAttribute($value)
    {
        $this->attributes['tel_enc'] = Cipher::encrypt($value);
    }

    public function setFaxEncAttribute($value)
    {
        $this->attributes['fax_enc'] = Cipher::encrypt($value);
    }

    public function setEmailEncAttribute($value)
    {
        $this->attributes['email_enc'] = Cipher::encrypt($value);
    }

    public function setSearchEmailEncAttribute($value)
    {
        $this->attributes['search_email_enc'] = Cipher::encrypt($value);
    }

    public function setPasswordEncAttribute($value)
    {
        $this->attributes['password_enc'] = Cipher::encrypt($value);
    }

    public function setPostalCodeEncAttribute($value)
    {
        $this->attributes['postal_code_enc'] = Cipher::encrypt($value);
    }

    public function setAddres1EncAttribute($value)
    {
        $this->attributes['addres1_enc'] = Cipher::encrypt($value);
    }

    public function setAddres2EncAttribute($value)
    {
        $this->attributes['addres2_enc'] = Cipher::encrypt($value);
    }

    public function setAddres3EncAttribute($value)
    {
        $this->attributes['addres3_enc'] = Cipher::encrypt($value);
    }

    public function setAddres4EncAttribute($value)
    {
        $this->attributes['addres4_enc'] = Cipher::encrypt($value);
    }

    public function setCreditCardTypeIdEncAttribute($value)
    {
        $this->attributes['credit_card_type_id_enc'] = Cipher::encrypt($value);
    }

    public function setCreditCardNoEncAttribute($value)
    {
        $this->attributes['credit_card_no_enc'] = Cipher::encrypt($value);
    }

    public function setCreditHolderNameEncAttribute($value)
    {
        $this->attributes['credit_holder_name_enc'] = Cipher::encrypt($value);
    }

    public function setCreditExpirationEncAttribute($value)
    {
        $this->attributes['credit_expiration_enc'] = Cipher::encrypt($value);
    }

    public function setCreditSecurityCdEncAttribute($value)
    {
        $this->attributes['credit_security_cd_enc'] = Cipher::encrypt($value);
    }

    public function setBankNameEncAttribute($value)
    {
        $this->attributes['bank_name_enc'] = Cipher::encrypt($value);
    }

    public function setBranchBankNameEncAttribute($value)
    {
        $this->attributes['branch_bank_name_enc'] = Cipher::encrypt($value);
    }

    public function setBankAccountTypeEncAttribute($value)
    {
        $this->attributes['bank_account_type_enc'] = Cipher::encrypt($value);
    }

    public function setBankAccountNumberEncAttribute($value)
    {
        $this->attributes['bank_account_number_enc'] = Cipher::encrypt($value);
    }

    public function setBankAccountNameEncAttribute($value)
    {
        $this->attributes['bank_account_name_enc'] = Cipher::encrypt($value);
    }

    public function setPayerNameEncAttribute($value)
    {
        $this->attributes['payer_name_enc'] = Cipher::encrypt($value);
    }

    public function cmThApplication()
    {
        return $this->hasMany('App\Models\CmThApplication', 'user_id');
    }

    public static function createUserForPayment()
    {
        $loginUser = null;

        $ret = userLogin::getLoginUser();
        if (isset($ret['userId'])) {
            return $ret['userId'];
        }

        $user = new User();
        $user->fill([
            'family_name_enc' => session('payment.family_name', ''),
            'first_name_enc' => session('payment.first_name', ''),
            'tel_enc' => session('payment.tel', ''),
            'email_enc' => session('payment.email', ''),
            'mailmagazine_recept_flg' => 0,
            'password_enc' => '',
            'member_status' => 0,
        ]);
        $user->save();

        /* @noinspection PhpUndefinedFieldInspection */
        return $user->user_id;
    }

    public static function getMembershipInfo($cmApplicationId)
    {
        return self::select('cm_tm_user.user_id', 'cm_tm_user.member_status')
            ->join('skyticket.cm_th_application', 'cm_tm_user.user_id', '=', 'cm_th_application.user_id')
            ->where('cm_th_application.cm_application_id', $cmApplicationId)
            ->first();
    }
}
