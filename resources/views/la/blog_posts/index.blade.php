@extends('la.layouts.app')

@section('contentheader_title')
    <a @ajaxload href="{{ url(config('laraadmin.adminRoute') . '/blog_posts') }}">@lang('la_blog_post.blog_posts')</a> :
@endsection
@section('contentheader_description', app('translator')->get('la_blog_post.blog_post_listing'))
@section('section', app('translator')->get('la_blog_post.blog_posts'))
@section('sub_section', app('translator')->get('common.listing'))
@section('htmlheader_title', app('translator')->get('la_blog_post.blog_post_listing'))

@section('headerElems')
    @la_access('Blog_posts', 'create')
        <button class="btn btn-success btn-sm pull-right" data-toggle="modal" data-target="#AddModal">@lang('la_blog_post.blog_post_add')</button>
    @endla_access
@endsection

@section('main-content')

    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="box box-success">
        <!--<div class="box-header"></div>-->
        <div class="box-body">
            <table id="dt_blog_posts" class="table table-bordered">
                <thead>
                    <tr class="success">
                        @foreach ($listing_cols as $col)
                            <th>{{ $module->fields[$col]['label'] ?? ucfirst($col) }}</th>
                        @endforeach
                        @if ($show_actions)
                            <th>@lang('common.actions')</th>
                        @endif
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>

    @la_access('Blog_posts', 'create')
        <div class="modal fade" id="AddModal" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">@lang('la_blog_post.blog_post_add')</h4>
                    </div>
                    {!! Form::open(['action' => 'App\Http\Controllers\LA\BlogPostsController@store', 'id' => 'blog_post-add-form']) !!}
                    <div class="modal-body">
                        <div class="box-body">
                            @la_form($module)

                            {{--
                            @la_input($module, 'title')
                            @la_input($module, 'url')
                            @la_input($module, 'category_id')
                            @la_input($module, 'status')
                            @la_input($module, 'author_id')
                            @la_input($module, 'tags')
                            @la_input($module, 'post_date')
                            @la_input($module, 'excerpt')
                            @la_input($module, 'banner')
                            @la_input($module, 'content')
                            --}}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('common.close')</button>
                        {!! Form::button(app('translator')->get('common.save'), ['class' => 'btn btn-success', 'type' => 'submit']) !!}
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    @endla_access

@endsection

@push('styles')
@endpush

@push('scripts')
    <script>
        var dt_blog_posts = null;
        var submitBtn = null;
        var formObj = null;

        $(function() {
            dt_blog_posts = $("#dt_blog_posts").DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ url(config('laraadmin.adminRoute') . '/blog_post_dt_ajax') }}",
                language: {
                    lengthMenu: "_MENU_",
                    search: "_INPUT_",
                    searchPlaceholder: '@lang('common.search')'
                },
                columns: [
                    @foreach ($listing_cols as $col)
                        {
                            data: '{{ $col }}',
                            name: '{{ $col }}'
                        },
                    @endforeach
                    @if ($show_actions)
                        {
                            data: 'dt_action',
                            name: 'dt_action',
                        },
                    @endif
                ],
                @if ($show_actions)
                    columnDefs: [{
                        orderable: false,
                        targets: [-1]
                    }],
                @endif
            });

            @la_access("Blog_posts", "create")
                // Create New BlogPost REST Request
                submitBtn = $('#blog_post-add-form button[type=submit]');
                formObj = $("#blog_post-add-form");

                formObj.validate({
                    submitHandler: function(form, event) {
                        event.preventDefault();
                        $.ajax({
                            url: formObj.attr('action'),
                            method: 'POST',
                            contentType: 'json',
                            headers: {
                                'X-CSRF-Token': '{{ csrf_token() }}'
                            },
                            data: getFormDataJSON(formObj),
                            beforeSend: function() {
                                submitBtn.html('<i class="fa fa-refresh fa-spin mr5"></i> Creating...');
                                submitBtn.prop('disabled', true);
                            },
                            success: function(data) {
                                console.log(data);
                                if (data.status == "success") {
                                    show_success("BlogPost Create", data);
                                    $('#AddModal').modal('hide')
                                    if (isset(data.redirect)) {
                                        window.location.href = data.redirect;
                                    }
                                } else {
                                    show_failure("BlogPost Create", data);
                                }
                                submitBtn.html('Save');
                                submitBtn.prop('disabled', false);
                            },
                            error: function(data) {
                                console.error(data);
                                show_failure("BlogPost Create", data);
                                submitBtn.html('Save');
                                submitBtn.prop('disabled', false);
                            }
                        });
                        return false;
                    }
                });
            @endla_access

            @la_access("Blog_posts", "edit")
                // Section for Updating fields via X-editable
                dt_blog_posts.on('draw', function() {
                    $('.update_field').editable({
                        container: 'body',
                        validate: function(value) {
                            var id = $(this).attr('id');
                            var field_name = $(this).attr('field_name');
                            // Make your validations here
                            if ($.trim(value) == '') {
                                return 'This field is required';
                            }
                            var formData = {};
                            formData[field_name] = value;
                            $.ajax({
                                url: "{{ url(config('laraadmin.adminRoute')) }}/blog_posts/" + id,
                                method: 'PUT',
                                contentType: 'json',
                                headers: {
                                    'X-CSRF-Token': '{{ csrf_token() }}'
                                },
                                data: JSON.stringify(formData),
                                success: function(data) {
                                    if (data.status == "success") {
                                        show_success("BlogPost Update", data);
                                    } else {
                                        show_failure("BlogPost Update", data);
                                    }
                                    if (isset(data.redirect)) {
                                        // window.location.href = data.redirect;
                                    }
                                },
                                error: function(data) {
                                    show_failure("BlogPost Update", data);
                                    if (isset(data.redirect)) {
                                        window.location.href = data.redirect;
                                    }
                                }
                            });
                        }
                    });
                });
            @endla_access
        });
    </script>
@endpush
