@extends('layouts.app')
@section('title', 'YAAMS: Flight list')
@section('content')
        <div class="container" >
            <h1 class="display-2">My flights</h1>
            <p class="lead">Here is a list of your filed flights and their PIREP status.</p>
            
            @empty($flights)
                <p>You have not logged any flights yet.</p>
            @endempty
            
            <table class="table">
            <thead class="table-dark">
                <tr>
                <th scope="col">PIREP ID</th>
                <th scope="col">Flight number</th>
                <th scope="col">From</th>
                <th scope="col">To</th>
                <th scope="col">Duration</th>
                <th scope="col">Last modified</th>
                <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($flights as $flight)
                <tr>  
                    <th scope="row"></th>
                    <td>{{ $flight->full_flight_number }}</td>
                    <td>{{ $flight->departure_icao }}</td>
                    <td>{{ $flight->arrival_icao }}</td>
                    <td>{{ $flight->flight_duration }}</td>
                    <td>{{ $flight->flight_date }}</td>
                    <td>Accepted ✅</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@endsection

