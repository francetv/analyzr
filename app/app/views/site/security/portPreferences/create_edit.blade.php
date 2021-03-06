@extends('site.layouts.default')

{{-- Content --}}
@section('content')
@section('breadcrumbs', Breadcrumbs::render('AddPolicy'))

	<style type="text/css">
		/* hide the extra submit button generated by jsonform */
		#additionalPortPreferencesFields [type="submit"]{display:none;}
	</style>

	<div class="page-header">
		<div class="row">
			<div class="col-md-9">
				<h5>@if(isset($portPreference->id)&& $mode=='edit'){{'Edit'.' '.'Port Preference:'}}@else{{'Create'.' '.'Port Preference:'}}@endif</h5>
			</div>
		</div>
	</div>

	{{-- Create/Edit cloud account Form --}}
	<form id="portPreferencesForm" class="form-horizontal" method="post" action="@if (isset($portPreference->id)){{ URL::to('security/portPreferences/' . $portPreference->id . '/edit') }}@endif" autocomplete="off">
		<!-- CSRF Token -->
		<input type="hidden" name="_token" value="{{{ csrf_token() }}}" />
		<!-- ./ csrf token -->

		
		<!-- name -->
		<div class="form-group {{{ $errors->has('username') ? 'error' : '' }}}">
			<label class="col-md-2 control-label" for="name">Project <font color="red">*</font></label>
			<div class="col-md-6">
				<input class="form-control" type="text" name="project" id="project" value="{{{ Input::old('project', isset($portPreference->project) ? $portPreference->project : null) }}}" required />
			</div>
		</div>

		<!-- name -->
		<div class="form-group {{{ $errors->has('username') ? 'error' : '' }}}">
			<label class="col-md-2 control-label" for="name">Accounts <font color="red">*</font></label>
			<div class="col-md-6">
			
				<select class="form-control" name="cloudAccountId" id="cloudAccountId" required="">
					@foreach($accounts as $account)
						<option value="{{$account->id}}">{{$account->name}}</option>
					@endforeach
				</select>
				
			</div>
		</div>
		
		<!-- ./ username -->
		<div id="additionalPortPreferencesFields">
			
		</div>				

		<!-- Form Actions -->
		<div class="form-group">
			<div class="col-md-offset-2 col-md-10">
				<a id="portpre_save_btn" href="{{ URL::to('security/portPreferences') }}" class="btn btn-default">Back</a>
				<button id="portpre_back_btn" type="submit" class="btn btn-primary">Save</button>
			</div>
		</div>
		<!-- ./ form actions -->
	</form>
@stop

@section('scripts')
<script src="{{asset('bower_components/jsonform/deps/underscore.js')}}"></script>
<script src="{{asset('bower_components/jsonform/lib/jsonform.js')}}"></script>
<script type="text/javascript">
	(function($){
		'use strict';
		var PORTSCHEMA = {{ json_encode($portSchema) }};
		var SAVED_PREFERENCES = {{ !empty($portPreference -> preferences) ? $portPreference -> preferences : 'null' }};
		$(function(){
			var $additionalPortPreferencesFields = $('#additionalPortPreferencesFields');
			var schema = PORTSCHEMA;
			var values = {};
				for(var credentialKey in SAVED_PREFERENCES) {
					if(!SAVED_PREFERENCES.hasOwnProperty(credentialKey) ){
						continue;
					}
					values['preferences['+credentialKey+']'] = SAVED_PREFERENCES[credentialKey];
				}
				$additionalPortPreferencesFields.empty().jsonForm({
			        schema: schema,
			        params: {
			        	fieldHtmlClass: 'form-control'
			        },
			        value: values
		      	});
		      	// Patch in bs3 classes
		      	$additionalPortPreferencesFields
		      		.find('.control-group')
		      		.removeClass('control-group')
		      		.addClass('form-group');
		      	$additionalPortPreferencesFields
		      		.find('.control-label')
		      		.addClass('col-md-2');
		      	$additionalPortPreferencesFields
		      		.find('.controls')
		      		.removeClass('controls')
		      		.addClass('col-md-6');
			var $portPreferencesForm = $('#portPreferencesForm');
			$portPreferencesForm.on('submit', function(e){

			});
		});
	})(jQuery);
</script>
@stop
