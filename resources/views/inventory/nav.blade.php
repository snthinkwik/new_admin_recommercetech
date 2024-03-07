<?php

use App\Colour;

$pleaseSelect = ["" => "--Please Select--"];
$colours = $pleaseSelect + array_combine(Colour::orderBy('pr_colour')->lists('pr_colour'), Colour::orderBy('pr_colour')->lists('pr_colour'));
?>


<div class="mb15">
    @if (Auth::user()->type !== 'user')
        @if(in_array(Auth::user()->admin_type, ['admin', 'manager']))
            <a href="{{route('inventory.index')}}">View Inventory</a> |

            <a href="{{route('inventory.create')}}" >Add Inventory</a> |

        @endif
    @endif
</div>