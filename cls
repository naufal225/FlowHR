[1mdiff --git a/app/Http/Controllers/EmployeeController/LeaveController.php b/app/Http/Controllers/EmployeeController/LeaveController.php[m
[1mindex 8ee430a..df271b6 100644[m
[1m--- a/app/Http/Controllers/EmployeeController/LeaveController.php[m
[1m+++ b/app/Http/Controllers/EmployeeController/LeaveController.php[m
[36m@@ -42,12 +42,16 @@[m [mpublic function index(Request $request)[m
         }[m
 [m
         $leaves = $query->paginate(10);[m
[31m-        $totalRequests = Leave::where('employee_id', $user->id)->count();[m
[31m-        $pendingRequests = Leave::where('employee_id', $user->id)->where('status_1', 'pending')->orWhere('status_2', 'pending')->count();[m
[31m-        $approvedRequests = Leave::where('employee_id', $user->id)->where('status_1', 'approved')->orWhere('status_2', 'approved')->count();[m
[31m-        $rejectedRequests = Leave::where('employee_id', $user->id)->where('status_1', 'rejected')->orWhere('status_2', 'rejected')->count();[m
[32m+[m[32m        $counts = $query->withFinalStatusCount()->first();[m
 [m
[31m-        return view('Employee.leaves.leave-show', compact('leaves', 'totalRequests', 'pendingRequests', 'approvedRequests', 'rejectedRequests'));[m
[32m+[m[32m        $totalRequests = (clone $query)->count();[m
[32m+[m[32m        $pendingRequests = (int) $counts->pending;[m
[32m+[m[32m        $approvedRequests = (int) $counts->approved;[m
[32m+[m[32m        $rejectedRequests = (int) $counts->rejected;[m
[32m+[m
[32m+[m[32m        $manager = User::where('role', Roles::Manager->value)->first();[m
[32m+[m
[32m+[m[32m        return view('Employee.leaves.leave-show', compact('leaves', 'totalRequests', 'pendingRequests', 'approvedRequests', 'rejectedRequests', 'manager'));[m
     }[m
 [m
     /**[m
[36m@@ -72,6 +76,10 @@[m [mpublic function store(Request $request)[m
             'reason' => 'required|string|max:1000',[m
         ]);[m
 [m
[32m+[m[32m        if(Auth::user()->division_id === null || trim(Auth::user()->division_id) === '') {[m
[32m+[m[32m            return redirect()->back()->with('error', 'You are not in a division. Please contact your administrator.');[m
[32m+[m[32m        }[m
[32m+[m
         $leave = new Leave();[m
         $leave->employee_id = Auth::id();[m
         $leave->date_start = $request->date_start;[m
[1mdiff --git a/app/Http/Controllers/EmployeeController/OvertimeController.php b/app/Http/Controllers/EmployeeController/OvertimeController.php[m
[1mindex b9b9ec6..118b1e9 100644[m
[1m--- a/app/Http/Controllers/EmployeeController/OvertimeController.php[m
[1m+++ b/app/Http/Controllers/EmployeeController/OvertimeController.php[m
[36m@@ -9,6 +9,7 @@[m
 use App\Models\Overtime;[m
 use App\Models\User;[m
 use Carbon\Carbon;[m
[32m+[m[32muse Illuminate\Support\Facades\Mail;[m
 [m
 class OvertimeController extends Controller[m
 {[m
[36m@@ -80,7 +81,7 @@[m [mpublic function store(Request $request)[m
             return back()->withErrors(['date_end' => 'Minimum overtime is 0.5 hours. Please adjust your end time.']);[m
         }[m
 [m
[31m-        Overtime::create([[m
[32m+[m[32m        $overtime = Overtime::create([[m
             'employee_id' => Auth::id(),[m
             'date_start'  => $start,[m
             'date_end'    => $end,[m
[36m@@ -89,8 +90,30 @@[m [mpublic function store(Request $request)[m
             'status_2'    => 'pending',[m
         ]);[m
 [m
[32m+[m[32m        // Send notification email to the approver[m
[32m+[m[32m        if ($overtime->approver) {[m
[32m+[m[32m            $linkTanggapan = route('employee.overtimes.show', $overtime->id);[m
[32m+[m
[32m+[m[32m            $hours = floor($overtimeMinutes / 60);[m
[32m+[m[32m            $minutes = $overtimeMinutes % 60;[m
[32m+[m[32m            $pesan = "Pengajuan lembur milik " . Auth::user()->name . " telah dilakukan perubahan data.[m
[32m+[m[32m                <br> Tanggal/Waktu Mulai: " . $request->date_start . "[m
[32m+[m[32m                <br> Tanggal/Waktu Akhir: " . $request->date_end . "[m
[32m+[m[32m                <br> Total Waktu: " . $hours . " hours " . $minutes . " minutes";[m
[32m+[m
[32m+[m[32m            Mail::to($overtime->approver->email)->send([m
[32m+[m[32m                new \App\Mail\SendMessage([m
[32m+[m[32m                    namaPengaju: Auth::user()->name,[m
[32m+[m[32m                    pesan: $pesan,[m
[32m+[m[32m                    namaApprover: $overtime->approver->name,[m
[32m+[m[32m                    linkTanggapan: $linkTanggapan,[m
[32m+[m[32m                    emailPengaju: Auth::user()->email,[m
[32m+[m[32m                )[m
[32m+[m[32m            );[m
[32m+[m[32m        }[m
[32m+[m
         return redirect()->route('employee.overtimes.index')[m
[31m-            ->with('success', 'Overtime submitted. Total: '. number_format($overtimeHours, 2) .' hours');[m
[32m+[m[32m            ->with('success', 'Overtime submitted. Total: '. $hours . ' hours ' . $minutes . ' minutes');[m
     }[m
 [m
     /**[m
[36m@@ -134,34 +157,27 @@[m [mpublic function update(Request $request, Overtime $overtime)[m
     {[m
         $user = Auth::user();[m
 [m
[31m-        // Pastikan user adalah pemilik pengajuan[m
         if ($user->id !== $overtime->employee_id) {[m
             abort(403, 'Unauthorized action.');[m
         }[m
 [m
[31m-        // Pastikan status masih pending[m
         if ($overtime->status_1 !== 'pending' || $overtime->status_2 !== 'pending') {[m
             return redirect()->route('employee.overtimes.show', $overtime->id)[m
                 ->with('error', 'You cannot update an overtime request that has already been processed.');[m
         }[m
 [m
[31m-        // Validasi input[m
         $request->validate([[m
             'date_start' => 'required|date_format:Y-m-d\TH:i',[m
             'date_end'   => 'required|date_format:Y-m-d\TH:i|after:date_start',[m
         ]);[m
 [m
[31m-        // Parsing waktu dari input request[m
         $start = Carbon::createFromFormat('Y-m-d\TH:i', $request->date_start, 'Asia/Jakarta');[m
         $end   = Carbon::createFromFormat('Y-m-d\TH:i', $request->date_end, 'Asia/Jakarta');[m
 [m
[31m-        // Hitung durasi lembur dalam menit[m
         $overtimeMinutes = $start->diffInMinutes($end);[m
 [m
[31m-        // Konversi ke jam (desimal)[m
         $overtimeHours = $overtimeMinutes / 60;[m
 [m
[31m-        // Minimal lembur 0.5 jam[m
         if ($overtimeHours < 0.5) {[m
             return back()->withErrors(['date_end' => 'Minimum overtime is 0.5 hours. Please adjust your end time.']);[m
         }[m
[36m@@ -170,10 +186,34 @@[m [mpublic function update(Request $request, Overtime $overtime)[m
         $overtime->date_start = $request->date_start;[m
         $overtime->date_end   = $request->date_end;[m
         $overtime->total      = $overtimeMinutes; // Disimpan dalam menit[m
[32m+[m[32m        $overtime->status_1   = 'pending';[m
[32m+[m[32m        $overtime->status_2   = 'pending';[m
         $overtime->save();[m
 [m
[32m+[m[32m        // Send notification email to the approver[m
[32m+[m[32m        if ($overtime->approver) {[m
[32m+[m[32m            $linkTanggapan = route('employee.overtimes.show', $overtime->id);[m
[32m+[m
[32m+[m[32m            $hours = floor($overtimeMinutes / 60);[m
[32m+[m[32m            $minutes = $overtimeMinutes % 60;[m
[32m+[m[32m            $pesan = "Pengajuan lembur milik " . Auth::user()->name . " telah dilakukan perubahan data.[m
[32m+[m[32m                <br> Tanggal/Waktu Mulai: " . $request->date_start . "[m
[32m+[m[32m                <br> Tanggal/Waktu Akhir: " . $request->date_end . "[m
[32m+[m[32m                <br> Total Waktu: " . $hours . " hours " . $minutes . " minutes";[m
[32m+[m
[32m+[m[32m            Mail::to($overtime->approver->email)->send([m
[32m+[m[32m                new \App\Mail\SendMessage([m
[32m+[m[32m                    namaPengaju: Auth::user()->name,[m
