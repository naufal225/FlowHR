@if ($errors->any())
<div class="px-4 py-3 mx-6 my-6 border rounded-lg bg-error-50 border-error-200 text-error-700">
    <ul class="pl-5 space-y-1 list-disc">
        @foreach ($errors->all() as $error)
        <li class="text-sm">{{ $error }}</li>
        @endforeach
    </ul>
    </div>
@endif


