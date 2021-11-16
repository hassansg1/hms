<?php

namespace App\Http\Controllers\Admin;

use App\Attendance;
use App\Department;
use App\Employee;
use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\ImageManagerStatic as Image;

use function Ramsey\Uuid\v1;

class EmployeeController extends Controller
{
    public function index()
    {
        $data = [
            'employees' => Employee::all()
        ];
        return view('admin.employees.index')->with($data);
    }

    public function create()
    {
        $data = [
            'departments' => Department::all(),
            'desgs' => ['HR manager', 'Marketing Lead', 'Marketing executive',
                'Senior Php Developer', 'Junior Php Developer', 'UI/UX designer',
                'Internee Developer', 'Content Writer', 'Marketing Intern'
            ]
        ];
        return view('admin.employees.create')->with($data);
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'first_name' => 'required',
            'last_name' => 'required',
            'sex' => 'required',
            'desg' => 'required',
            'department_id' => 'required',
            'salary' => 'required|numeric',
            'dob' => 'required',
            'join_date' => 'required',
            'email' => 'required|email',
            'photo' => 'image|nullable',
            'password' => 'required|confirmed|min:6',
            'password_confirmation' => 'required',
            'next_of_kim' => 'required',
            'contact_next_of_kim' => 'required',
            'job_type' => '',
            'blood_group' => 'required',
            'contact' => 'required',
            'email_office_use' => 'required',
            'job_nature' => '',
            'probition_from' => 'required',
            'probition_to' => 'required',
            'next_review_date' => 'required',
            'basic_salary' => 'required',
            'housing_salary' => 'required',
            'transportaion_allowance' => 'required',
            'gross_total' => 'required'

        ]);

        $employeeDetails = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'sex' => $request->sex,
            'dob' => $request->dob,
            'join_date' => $request->join_date,
            'desg' => $request->desg,
            'department_id' => $request->department_id,
            'salary' => $request->salary,
            'email' => $request->email,
            'photo' => 'user.png',
            'next_of_kim' => $request->next_of_kim,
            'contact_next_of_kim' => $request->contact_next_of_kim,
            'job_type' => $request->job_type,
            'blood_group' => $request->blood_group,
            'contact_no' => $request->contact_no,
            'email_office_use' => $request->email_office_use,
            'job_nature' => $request->job_nature,
            'next_review_date' => $request->next_review_date,
            'basic_salary' => $request->basic_salary,
            'housing_salary' => $request->housing_salary,
            'transportaion_allowance' => $request->transportaion_allowance
        ];

        // Photo upload
        if ($request->hasFile('photo')) {
            // GET FILENAME
            $filename_ext = $request->file('photo')->getClientOriginalName();
            // GET FILENAME WITHOUT EXTENSION
            $filename = pathinfo($filename_ext, PATHINFO_FILENAME);
            // GET EXTENSION
            $ext = $request->file('photo')->getClientOriginalExtension();
            //FILNAME TO STORE
            $filename_store = $filename . '_' . time() . '.' . $ext;
            // UPLOAD IMAGE
            // $path = $request->file('photo')->storeAs('public'.DIRECTORY_SEPARATOR.'employee_photos', $filename_store);
            // add new file name
            $image = $request->file('photo');
            $image_resize = Image::make($image->getRealPath());
            $image_resize->resize(300, 300);
            $image_resize->save(public_path(DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'employee_photos' . DIRECTORY_SEPARATOR . $filename_store));
            $employeeDetails['photo'] = $filename_store;
        }

        Employee::create($employeeDetails);

        $request->session()->flash('success', 'Employee has been successfully added');
        return back();
    }

    public function attendance(Request $request)
    {
        $data = [
            'date' => null
        ];
        if ($request->all()) {
            $date = Carbon::create($request->date);
            $employees = $this->attendanceByDate($date);
            $data['date'] = $date->format('d M, Y');
        } else {
            $employees = $this->attendanceByDate(Carbon::now());
        }
        $data['employees'] = $employees;
        // dd($employees->get(4)->attendanceToday->id);
        return view('admin.employees.attendance')->with($data);
    }

    public function attendanceByDate($date)
    {
        $employees = DB::table('employees')->select('id', 'first_name', 'last_name', 'desg', 'department_id')->get();
        $attendances = Attendance::all()->filter(function ($attendance, $key) use ($date) {
            return $attendance->created_at->dayOfYear == $date->dayOfYear;
        });
        return $employees->map(function ($employee, $key) use ($attendances) {
            $attendance = $attendances->where('employee_id', $employee->id)->first();
            $employee->attendanceToday = $attendance;
            $employee->department = Department::find($employee->department_id)->name;
            return $employee;
        });
    }

    public function destroy($employee_id)
    {
        $employee = Employee::findOrFail($employee_id);
        $user = User::findOrFail($employee->user_id);
        // detaches all the roles
        DB::table('leaves')->where('employee_id', '=', $employee_id)->delete();
        DB::table('attendances')->where('employee_id', '=', $employee_id)->delete();
        DB::table('expenses')->where('employee_id', '=', $employee_id)->delete();
        $employee->delete();
        $user->roles()->detach();
        // deletes the users
        $user->delete();
        request()->session()->flash('success', 'Employee record has been successfully deleted');
        return back();
    }

    public function attendanceDelete($attendance_id)
    {
        $attendance = Attendance::findOrFail($attendance_id);
        $attendance->delete();
        request()->session()->flash('success', 'Attendance record has been successfully deleted!');
        return back();
    }

    public function employeeProfile($employee_id)
    {
        $employee = Employee::findOrFail($employee_id);
        return view('admin.employees.profile')->with('employee', $employee);
    }
}
