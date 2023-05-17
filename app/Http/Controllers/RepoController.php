<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RepoController extends Controller
{
    public function findRepo() {
        return view('repositories.find');
    }
    public function repoList(Request $request) {
        $request->validate([
            'username' => 'required'
        ]);

        $repo = GitController::getRepositories($request->input('username'));

        return view('repositories.list')->with('data', ['username' => $request->input('username'), 'repo' => $repo]);
    }

    public function repo($username, $repoName) {
        $repo = GitController::getRepository($username, $repoName);

        return view('repositories.repo')->with('data', ['username' => $username, 'repo' => $repo]);
    }

    public function repoTree($username, $repoName, $sha) {
        $tree = GitController::getTree($username, $repoName, $sha);

        return $tree;
    }

    public function getBlob($username, $repoName, $branch, $filename, $sha) {
        $blob = GitController::getBlob($username, $repoName, $branch, $sha);
        $blob->commit = GitController::getLastCommit($username, $repoName, $branch, $sha);

        $blob->content = base64_decode($blob->content);

        return view('repositories.file')->with('data', [
            'username' => $username,
            'repoName' => $repoName,
            'branch' => $branch,
            'filename' => $filename,
            'obj' => $blob]);
    }

    public function getParentCommit(string $sha, Request $request) {
        $request->validate([
            'username' => 'required',
            'repoName' => 'required',
            'branch' => 'required',
            'filename' => 'required',

        ]);
        $username = $request->input('username');
        $repoName = $request->input('repoName');
        $branch = $request->input('branch');
        $filename = $request->input('filename');

        $f = false;
        $fileSha = '';

        do {
            $commit = GitController::getCommit($username, $repoName, $sha);

            if($commit == null) {
                return;
            }

            foreach ($commit->files as $file) {
                if (str_ends_with($file->filename, $filename)) {
                    $fileSha = $file->sha;
                    $f = true;
                    break;
                }
            }

            if($f) {
                break;
            }

            if(count($commit->parents) == 0) {
                break;
            }

            $sha = $commit->parents[0]->sha;
        } while(!$f);

        $blob = GitController::getBlob($username, $repoName, $branch, $fileSha);
        $blob->commit = $commit;
        $blob->content = base64_decode($blob->content);
        return view('repositories.file')->with('data', [
            'username' => $username,
            'repoName' => $repoName,
            'branch' => $branch,
            'filename' => $filename,
            'obj' => $blob]);
    }
}
