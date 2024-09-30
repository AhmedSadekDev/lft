<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Car\StoreRequest;
use App\Http\Requests\Admin\Car\UpdateRequest;
use App\Models\Car;
use Illuminate\Http\Request;

class CarController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:cars.index')->only('index');
        $this->middleware('permission:cars.create')->only(['create', 'store']);
        $this->middleware('permission:cars.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:cars.delete')->only('destroy');   
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'cars' => Car::all(),
        ];
        return view('admin.cars.index', $input);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input = [
            'method'    => 'POST',
            'action'    => route('cars.store') 
        ];

        return view('admin.cars.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $car = Car::create($request->validated());

        return redirect()->route('cars.index')
            ->with('success', __('alerts.added_successfully'));
    }


    public function edit(Car $car)
    {
        $input = [
            'method'    => 'PUT',
            'action'    => route('cars.update', $car->id),
            'car'   => $car,
        ];

        return view('admin.cars.edit', $input);
    }


    public function update(UpdateRequest $request, Car $car)
    {
        $car->update($request->validated());
        return redirect()->route('cars.index')
            ->with('success', __('alerts.updated_successfully'));
    }


    public function destroy(Car $car)
    {
        $car->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }

}
