
@extends('layouts.admin')

@section('title')
    マウント
@endsection

@section('content-header')
    <h1>マウント<small>サーバーの追加のマウントポイントを構成および管理します。</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">管理者</a></li>
        <li class="active">マウント</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">マウントリスト</h3>

                    <div class="box-tools">
                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newMountModal">新規作成</button>
                    </div>
                </div>

                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Source</th>
                            <th>Target</th>
                            <th class="text-center">Eggs</th>
                            <th class="text-center">Nodes</th>
                            <th class="text-center">Servers</th>
                        </tr>

                        @foreach ($mounts as $mount)
                            <tr>
                                <td><code>{{ $mount->id }}</code></td>
                                <td><a href="{{ route('admin.mounts.view', $mount->id) }}">{{ $mount->name }}</a></td>
                                <td><code>{{ $mount->source }}</code></td>
                                <td><code>{{ $mount->target }}</code></td>
                                <td class="text-center">{{ $mount->eggs_count }}</td>
                                <td class="text-center">{{ $mount->nodes_count }}</td>
                                <td class="text-center">{{ $mount->servers_count }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newMountModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.mounts') }}" method="POST">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true" style="color: #FFFFFF">&times;</span>
                        </button>

                        <h4 class="modal-title">マウントの作成</h4>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label for="pName" class="form-label">名前</label>
                                <input type="text" id="pName" name="name" class="form-control" />
                                <p class="text-muted small">これを他と区別するためのユニークな名前。</p>
                            </div>

                            <div class="col-md-12">
                                <label for="pDescription" class="form-label">説明</label>
                                <textarea id="pDescription" name="description" class="form-control" rows="4"></textarea>
                                <p class="text-muted small">このマウントの詳細な説明、191文字以下である必要があります。</p>
                            </div>

                            <div class="col-md-6">
                                <label for="pSource" class="form-label">ソース</label>
                                <input type="text" id="pSource" name="source" class="form-control" />
                                <p class="text-muted small">ホストシステム上のファイルパス、コンテナにマウントするためのもの。</p>
                            </div>

                            <div class="col-md-6">
                                <label for="pTarget" class="form-label">ターゲット</label>
                                <input type="text" id="pTarget" name="target" class="form-control" />
                                <p class="text-muted small">コンテナ内でアクセス可能なマウント先。</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">読み取り専用</label>

                                <div>
                                    <div class="radio radio-success radio-inline">
                                        <input type="radio" id="pReadOnlyFalse" name="read_only" value="0" checked>
                                        <label for="pReadOnlyFalse">False</label>
                                    </div>

                                    <div class="radio radio-warning radio-inline">
                                        <input type="radio" id="pReadOnly" name="read_only" value="1">
                                        <label for="pReadOnly">True</label>
                                    </div>
                                </div>

                                <p class="text-muted small">コンテナ内でのマウントが読み取り専用であるかどうか。</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">ユーザーがマウント可能</label>

                                <div>
                                    <div class="radio radio-success radio-inline">
                                        <input type="radio" id="pUserMountableFalse" name="user_mountable" value="0" checked>
                                        <label for="pUserMountableFalse">False</label>
                                    </div>

                                    <div class="radio radio-warning radio-inline">
                                        <input type="radio" id="pUserMountable" name="user_mountable" value="1">
                                        <label for="pUserMountable">True</label>
                                    </div>
                                </div>

                                <p class="text-muted small">ユーザーがこれを自分でマウントできるかどうか。</p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        {!! csrf_field() !!}
                        <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">キャンセル</button>
                        <button type="submit" class="btn btn-success btn-sm">作成</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
