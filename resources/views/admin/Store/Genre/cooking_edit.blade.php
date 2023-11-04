<div class="block-header block-header-default col-md-9" style="margin-top: 25px;">
    <h3 class="block-title">総件数 : {{ $cookingGenreGroups->count() }}件</h3>
</div>
<div class="block col-md-9 item-list-pc">
    <div class="block-content">
        <form action="{{ route('admin.store.genre.cooking.edit', ['id' => $id]) }}" method="post" class="js-validation-material">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
            <input type="hidden" name="big_genre" id="big_genre" value="{{$cookingGenre}}">
            @if(!$cookingGenreGroups->isEmpty())
                <label for="genreType" style="margin-bottom:10px">料理ジャンル一覧/編集</label>
                @foreach($cookingGenreGroups as $i => $genreGroup)
                    <input type="hidden" id="cooking_genre_group_id_{{$i}}" name="cooking_genre_group_id[]" value="{{$genreGroup['genreGroupId']}}">
                    <div class="form-group">
                        <div class="float-left">{{$i+1}}</div>
                        <div class="row" id="genreType">
                            <div class="col-3">
                                <select class="form-control" id="cooking_middle_genre_{{$i}}" name="cooking_middle_genre[]" required>
                                    <option value="">- ジャンル(中) -</option>
                                    @foreach($cookingMiddleGenres as $key => $value)
                                        <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('cooking_middle_genre.'.$i, $genreGroup['middleGenre'])) selected @endif>{{ $value->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <select class="form-control" id="cooking_small_genre_{{$i}}" name="cooking_small_genre[]" required>
                                    <option value="">- ジャンル(小) -</option>
                                    @foreach($genreGroup['smallGenres'] as $key => $value)
                                        <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('cooking_small_genre.'.$i, $genreGroup['smallGenre'])) selected @endif>{{ $value->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <select class="form-control" id="cooking_small2_genre_{{$i}}" name="cooking_small2_genre[]">
                                    <option value="">- ジャンル(小)2 -</option>
                                    @foreach($genreGroup['small2Genres'] as $key => $value)
                                        <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('cooking_small2_genre.'.$i, $genreGroup['genre']->genre_cd)) selected @endif>{{ $value->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-2">
                                <select class="form-control" id="cooking_delegate_{{$i}}" name="cooking_delegate[]">
                                    @foreach (config('const.genre.delegate') as $parentKey => $delegate)
                                        @foreach ($delegate as $childKey => $word)
                                            <option value="{{ $parentKey }}" @if (old("cooking_delegate.".$i, $genreGroup['delegate'])===$childKey) selected @endif>{{ $word }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-1" id="delete">
                                <a href="javascript:void(0)" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Delete" id="{{$genreGroup['genreGroupId']}}">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
            <div class="block-content block" data-nodata="cooking">
                <div class="text-center">料理ジャンルの登録がありません</div>
            </div>
            @endif

            <!-- Start addForm -->
            <div id="store_cooking_form" class="mb-3">
            </div>
            <!-- End Form -->


            <div class="form-group">
                <div class="float-left">&nbsp;</div>
                <div class="row" id="genreType">
                    <div class="col-11">
                    </div>
                    <div class="col-1">
                        <button type="button" id="add" class="btn btn-sm btn-secondary" data-toggle="tooltip" data-genre="cooking" title="Add">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="text-right">
                    <button type="submit" class="btn btn-alt-primary" value="update" data-save="cooking">保存</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="block col-md-9 item-list-sp">
    <div class="block-content" style="padding:0;">
        <form action="{{ route('admin.store.genre.cooking.edit', ['id' => $id]) }}" method="post" class="js-validation-material">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
            <input type="hidden" name="big_genre" id="big_genre" value="{{$cookingGenre}}">
            @if(!$cookingGenreGroups->isEmpty())
            <label for="genreType" style="margin-bottom:10px;padding-left:8px;padding-top:8px;">料理ジャンル一覧/編集</label>
            @foreach($cookingGenreGroups as $i => $genreGroup)
            <div class="row">
                <div class="col-12">
                    <input type="hidden" id="cooking_genre_group_id_{{$i}}" name="cooking_genre_group_id[]" value="{{$genreGroup['genreGroupId']}}">
                    <div class="form-group" id="genreType">
                        <table>
                            <tr>
                                <td rowspan="4" style="width: 24px; padding: 8px; vertical-align: top;">
                                    {{$i+1}}
                                </td>
                                <td>
                                    <select class="form-control" id="cooking_middle_genre_{{$i}}" name="cooking_middle_genre[]" required>
                                        <option value="">- ジャンル(中) -</option>
                                        @foreach($cookingMiddleGenres as $key => $value)
                                        <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('cooking_middle_genre.'.$i, $genreGroup['middleGenre'])) selected @endif>{{ $value->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td rowspan="4" style="width:48px; padding:8px;">
                                    <div id="delete">
                                        <a href="javascript:void(0)" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Delete" id="{{$genreGroup['genreGroupId']}}">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <select class="form-control" id="cooking_small_genre_{{$i}}" name="cooking_small_genre[]" required>
                                        <option value="">- ジャンル(小) -</option>
                                        @foreach($genreGroup['smallGenres'] as $key => $value)
                                        <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('cooking_small_genre.'.$i, $genreGroup['smallGenre'])) selected @endif>{{ $value->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <select class="form-control" id="cooking_small2_genre_{{$i}}" name="cooking_small2_genre[]">
                                        <option value="">- ジャンル(小)2 -</option>
                                        @foreach($genreGroup['small2Genres'] as $key => $value)
                                        <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('cooking_small2_genre.'.$i, $genreGroup['genre']->genre_cd)) selected @endif>{{ $value->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <select class="form-control" id="cooking_delegate_{{$i}}" name="cooking_delegate[]">
                                        @foreach (config('const.genre.delegate') as $parentKey => $delegate)
                                        @foreach ($delegate as $childKey => $word)
                                        <option value="{{ $parentKey }}" @if (old("cooking_delegate.".$i, $genreGroup['delegate'])===$childKey) selected @endif>{{ $word }}</option>
                                        @endforeach
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach
            @else
            <div class="block-content block" data-nodata="cooking">
                <div class="text-center">料理ジャンルの登録がありません</div>
            </div>
            @endif

            <!-- Start addForm -->
            <div id="store_cooking_form" class="mb-3">
            </div>
            <!-- End Form -->

            <div class="form-group item-list-pc">
                <div class="float-left">&nbsp;</div>
                <div class="row" id="genreType">
                    <div class="col-11">
                    </div>
                    <div class="col-1">
                        <button type="button" id="add" class="btn btn-sm btn-secondary" data-toggle="tooltip" data-genre="cooking" title="Add">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-group item-list-pc">
                <div class="text-right">
                    <button type="submit" class="btn btn-alt-primary" value="update" data-save="cooking">保存</button>
                </div>
            </div>

            <div class="form-group item-list-sp">
                <table>
                    <tr>
                        <td style="width: 24px; padding: 8px; vertical-align: top;">
                        </td>
                        <td>
                        </td>
                        <td style="width:48px; padding:8px;">
                            <button type="button" id="add" class="btn btn-sm btn-secondary" data-toggle="tooltip" data-genre="cooking" title="Add">
                                <i class="fa fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-group item-list-sp">
                <table>
                    <tr>
                        <td style="width: 24px; padding: 8px; vertical-align: top;"></td>
                        <td></td>
                        <td style="width:120px; padding:8px; text-align:right;">
                            <button type="submit" class="btn btn-alt-primary" value="update" data-save="cooking">保存</button>
                        </td>
                    </tr>
                </table>
            </div>

        </form>
    </div>
</div>
<button type="button" class="btn btn-secondary" onclick="location.href='{{ session('storeGenreRedirectTo', route('admin.store')) }}'">戻る</button>
