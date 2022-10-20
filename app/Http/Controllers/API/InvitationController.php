<?php

namespace App\Http\Controllers\API;

use App\Helpers\Constant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHandler;
use App\Models\Company;
use App\Models\Invitations;
use App\Helpers\GlobalFunction;
use DB;
use Illuminate\Support\Facades\Auth;
use Validator;

class InvitationController extends Controller
{
    // get details of all invites
    public function index()
    {
    }
    public function getAllInvites(Request $request)
    {
        dd(DB::table('invitations')->get()->toArray());
    }
    public function sendInvites(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'contact_first_name' => 'required',
            'contact_last_name' => 'required',
            'contact_email' => 'required|email',
            'contact_number' => 'required',
            'invitation_type' => 'required',
            'company_name' => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseHandler::validationError($validator->errors());
        }

        try {
            

            DB::beginTransaction();
            // $now = new DateTime();
            if (Company::where('company_name', $request->company_name)->exists()) {
                $companyId = Company::where('company_name', $request->company_name)->select('id')->first();
            } else {
                $companyData = [
                    'company_name' => $request->company_name,
                    'company_department' => (isset($request->company_department) && !empty($request->company_department)) ? $request->company_department : '',
                    'company_type' => (isset($request->company_type) && !empty($request->company_type)) ? $request->company_type : '',
                    'company_started_at' => (isset($request->company_started_at) && !empty($request->company_started_at)) ? $request->company_started_at : null,
                    'company_ended_at' => (isset($request->company_ended_at) && !empty($request->company_ended_at)) ? $request->company_ended_at : null,
                    'is_invite' => (isset($request->is_invite) && !empty($request->is_invite)) ? $request->is_invite : ''
                ];

                $companyId = Company::create($companyData)->id;
            }

            if (isset($companyId->id) && !empty($companyId->id)) {
                $invitationData = [
                    'contact_first_name' => $request->contact_first_name,
                    'contact_last_name' => $request->contact_last_name,
                    'contact_email' => $request->contact_email,
                    'contact_number' => $request->contact_number,
                    'invitation_type' => $request->invitation_type,
                    'company_id' => $companyId->id
                ];

                $inviteDetail = Invitations::create($invitationData);
                if($inviteDetail) {
                    $emailType = 'invitation';
                    $title = 'Invitation email';
                    // $company = Company::select('company_name')->find(Auth()->user()->company_id);
                    // $companyName = $company->company_name;
                    $emailData = [
                        'email_message' => 'This is the invitation from the partner company name ',
                        'url' => 'https://www.remotestack.io'
                    ];
                    $recieverEmail = $inviteDetail['contact_email'];
                    $senderEmail = Constant::EMAIL_SENDER[$emailType];
                   $sendEmail = GlobalFunction::sendEmail($emailType, $title, $emailData, $recieverEmail, $senderEmail);
                   if($sendEmail) {
                    $inviteDetail->update(['status', true]);
                    // $updateCompanyInvite = Company::whereId($companyId)->update(['is_invite', true]);
                    return ResponseHandler::success($inviteDetail);
                   }
                   return ResponseHandler::serverError('There might be a problem in sending email.');
                }
                return ResponseHandler::serverError('There might be a problem in creating an invite.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHandler::serverError($e);
        } finally {
            DB::commit();
        }

        
    }
}
