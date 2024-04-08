<?php

namespace App\Http\Controllers;

use App\Models\Aircraft;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AircraftController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    public function index(Request $request) {
        $currentActiveAirline = $request->session()->get('activeairline');

        if($request->getMethod() == "POST"){
            $validated = $request->validate([
                'registration' => 'required|max:6',
                'manufacturer' => 'required',
                'model' => 'required',
                'current_loc' => 'required|max:4',
                'remarks' => 'nullable',
            ]);

            if (Aircraft::where('active', 1)->where('registration', '=', $request->post('registration'))->where('used_by', '=', $currentActiveAirline->airline->id)->count() >= 1) {
                throw ValidationException::withMessages(['registration' => 'An active aircraft with this tail number already exist in this airline. Please set the aircraft inactive or choose another tail number.']);
            } else {
                Aircraft::create($validated + ['used_by' => $currentActiveAirline->airline->id]);
            }
        }

        $limit = max(env('FLEET_PAGE_LIMIT'), 1);
        $maxEntries = Aircraft::count();
        $maxPages = (int)ceil($maxEntries/$limit);
        $page = (int)$request->get('page', 1);
        $page = min(max(1, $page), $maxPages);
        $offset = ($page -1) * $limit;

        $fleet = Aircraft::query()
        ->orderBy('created_at', 'DESC')
        ->where('used_by', '=', $currentActiveAirline->airline->id )
        ->offset($offset)
        ->limit($limit)
        ->get();

        return view('fleet.index', ['fleet' => $fleet, 'maxPages' => $maxPages, 'currentPage' => $page]);
    }

    public function edit(Request $request, Aircraft $aircraft) {
        if(auth()->user()->can('add aircraft')) {
            $currentActiveAirline = $request->session()->get('activeairline');
    
            //Check if users airline owns the aircraft
            if(!$currentActiveAirline->airline->id = $aircraft->airline->id) {
                dd($currentActiveAirline->airline->id);
                dd($aircraft->airline->id);  
                return redirect()->route('dashboard')->with('error', 'You did something nasty!');
            }
    
            $gotStatus = $request->post('active');
    
            if($gotStatus == "on"){
                $finalStatus = true;
            } else {
                $finalStatus = false;
            }
    
            if($request->getMethod() == "POST"){
                $validated = $request->validate([
                    'registration' => 'required|uppercase|max:6',
                    'manufacturer' => 'required',
                    'model' => 'required',
                    'remarks' => 'nullable',
                ]);
    
                $targetAircraft = Aircraft::find($aircraft->id);
                $targetAircraft->registration = $request->post('registration');
                $targetAircraft->used_by = $currentActiveAirline->airline->id;
                $targetAircraft->manufacturer = $request->post('manufacturer');
                $targetAircraft->model = $request->post('model');
                $targetAircraft->active = $finalStatus;
                $targetAircraft->remarks = $request->post('remarks');
    
                if ($targetAircraft->isDirty('registration') || $targetAircraft->isDirty('active')) {
    
                    $existingAircraft = Aircraft::where('registration', $request->post('registration'))
                        ->where('used_by', $currentActiveAirline->airline->id)
                        ->where('id', '<>', $aircraft->id) // Exclude current aircraft
                        ->where('active', true)
                        ->exists();
    
                    if ($existingAircraft) {
                        throw ValidationException::withMessages(['registration' => 'An active aircraft with this tail number already exists in this airline. Please set the aircraft inactive or choose another tail number.']);
                    }
                }
    
                $targetAircraft->save();
    
                return redirect()->route('fleetmanager');
            }
            return view('fleet.edit', ['aircraft' => $aircraft ]);
        } else {
            return redirect()->route('dashboard')->with('error', "You did something nasty. You don't have the permission to edit aircraft.");
        }    
    }
}
