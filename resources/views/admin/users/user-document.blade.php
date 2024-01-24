<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#user_document"> User Document </a>
<div class="panel panel-default collapse" id="user_document">
    <div class="panel-body">

        <table class="table table-responsive">
            <tr>
                <th>Terms & Conditions</th>
                <th>Ltd Company Registration Certificate or Sole Trader Self Assessment letter</th>
                <th>Director or Sole Trader ID i.e Driving Licence or Passport</th>
                <th>Proof of address for the Company Director / Sole Trader</th>
                <th>Proof of address for the business i.e Company utility bill</th>
            </tr>
            <tr>
                <td>
                    @if(!is_null($user->document->terms_conditions))
                        <a href="{{env('TRG_UK_URL').'/'.$user->document->terms_conditions}}"> Download</a>
                    @else
                        Not Upload
                    @endif

                </td>
                <td>
                    @if(!is_null($user->document->company_registration_certificate_self_assesment_letter))
                        <a href="{{env("TRG_UK_URL").'/'.$user->document->company_registration_certificate_self_assesment_letter}}"> Download</a>
                    @else
                        Not Upload
                    @endif

                </td>
                <td>
                    @if(!is_null($user->document->id_proof))
                        <a href="{{env("TRG_UK_URL").'/'. $user->document->id_proof}}"> Download</a>
                    @else
                        Not Upload
                    @endif
                </td>
                <td>
                    @if(!is_null($user->document->proof_of_address_for_the_company_director))
                        <a href="{{env("TRG_UK_URL").'/'. $user->document->proof_of_address_for_the_company_director}}"> Download</a>
                    @else
                        Not Upload
                    @endif
                </td>

                <td>
                    @if(!is_null($user->document->proof_of_address_for_the_business))
                        <a href="{{env("TRG_UK_URL").'/'.$user->document->proof_of_address_for_the_business}}"> Download</a>
                    @else
                        Not Upload
                    @endif
                </td>
            </tr>
        </table>



    </div>
</div>
