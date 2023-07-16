<?php namespace Lulapay\Transaction\Models;

use Model;

/**
 * Model
 */
class MailTemplate extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'system_mail_templates';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

}
