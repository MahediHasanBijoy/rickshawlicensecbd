@php
    use Rakibhstu\Banglanumber\NumberToBangla;
    $numto = new NumberToBangla();
@endphp
<x-filament-panels::page>
    {{-- Page content --}}
    {{ $this->form }}
     
     <div style="width:100%;display:flex;justify-content:flex-end;margin-top: -25px;">
        <span style="font-weight:bold;">
            মোট ফেরত: {{ $numto->bnCommaLakh(($this->getTableQuery()->sum('yearly_fee_refund'))
            + $this->getTableQuery()->sum('security_fee_refund')) }} /=
        </span>
     </div>
     <div style="width:100%;display:flex;justify-content:flex-end;margin-top: -25px;">
        <span style="font-weight:bold;">
            আবেদন ফি: {{ $numto->bnCommaLakh(($this->getTableQuery()->sum('fee'))) }}/=  
        </span>  
    </div>
    <div style="width:100%;display:flex;justify-content:flex-end;margin-top: -25px;">
        <span style="font-weight:bold;">
            পে অর্ডার: {{ $numto->bnCommaLakh(($this->getTableQuery()->sum('yearly_fee'))) }}/=  
        </span>  
    </div>
    <div style="width:100%;display:flex;justify-content:flex-end;margin-top: -25px;">
        <span style="font-weight:bold;">
          বার্ষিক ফি : {{ $numto->bnCommaLakh(($this->getTableQuery()->sum('yearly_fee_paid'))) }}/=  
        </span>  
    </div>
    <div style="width:100%;display:flex;justify-content:flex-end;margin-top: -25px;">
        <span style="font-weight:bold;">
            সিকিউরিটি ফি: {{ $numto->bnCommaLakh(($this->getTableQuery()->sum('security_fee'))) }}/=  
        </span>  
    </div>
     {{ $this->table }}
</x-filament-panels::page>
