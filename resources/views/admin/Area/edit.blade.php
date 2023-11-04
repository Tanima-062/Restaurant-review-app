@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('content')
    <!-- Content -->
    <div class="content">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
        <h2 class="content-heading">エリア編集</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form action="{{ route('admin.area.edit', ['id' => $area->id]) }}" method="post" class="js-validation-material">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <input type="hidden" name="old_area_cd" value="{{ $area['area_cd'] }}">
                    <input type="hidden" name="old_area_path" value="{{ $area['path'] }}">
                    <input type="hidden" name="old_area_level" value="{{ $area['level'] }}">
                    <input type="hidden" name="old_area_name" value="{{ $area['name'] }}">
                    <input type="hidden" name="old_published" value="{{ $area['published'] }}">
                    <input type="hidden" name="old_weight" value="{{ $area['weight'] }}">
                    <label for="areaType" style="margin-bottom:10px">エリア階層</label>
                    <div class="form-group row" id="areaType">
                        <div class="col-3">
                            <select class="form-control" id="big_area" name="big_area">
                                <option value="none">- エリア(大) -</option>
                                @foreach($bigAreas as $key => $value)
                                    <option value="{{ strtolower($value->area_cd) }}" @if(strtolower($value->area_cd) == old('big_area', $bigArea)) selected @endif>@if(!empty($area->middle_area)) {{$bigArea}} @endif{{ $value->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-3">
                            <select class="form-control" id="middle_area" name="middle_area" @if(empty($bigArea)) style="display: none" @endif>
                                <option value="">- エリア(中) -</option>
                                @foreach($middleAreas as $key => $value)
                                    <option value="{{ strtolower($value->area_cd) }}" @if(strtolower($value->area_cd) == old('middle_area', $middleArea)) selected @endif>{{ $value->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="name" name="name" value="{{old('name', $area->name)}}" required>
                            <label for="name">名前<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="area_cd" name="area_cd" value="{{old('area_cd', $area->area_cd)}}" required>
                            <label for="area_cd">エリアコード<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="weight" name="weight" value="{{old('weight', $area->weight)}}">
                            <label for="weight">優先度(最大値9999.99)</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="sort" name="sort" value="{{old('sort', $area->sort)}}">
                            <label for="sort">ソート順</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <div class="text-right">
                                <label class="css-control css-control css-control-primary css-switch">
                                    @php
                                        $check = $area->published ? true: false;
                                        if (!empty(old('redirect_to'))) {
                                            if (old('published') !== '1') {
                                                $check = false;
                                            } else {
                                                $check = true;
                                            }
                                        }
                                    @endphp
                                    <input type="checkbox" class="css-control-input" id="published" name="published" value="1" @if($check) checked @endif>
                                    <span class="css-control-indicator"></span> 公開する
                                </label>
                            </div>
                            <label for="published">公開/非公開</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="text-right">
                            <button type="submit" class="btn btn-alt-primary" value="update">更新</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="location.href='{{ old('redirect_to', url()->previous()) }}'">戻る</button>

    </div>
    <!-- END Content -->
    @include('admin.Layouts.js_files')

    <script src="{{ asset('vendor/admin/assets/js/area.js').'?'.time() }}"></script>
@endsection

@include('admin.Layouts.footer')
