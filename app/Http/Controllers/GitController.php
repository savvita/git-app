<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GitController extends Controller
{
    private const BASE_URL = 'https://api.github.com/';

    private static function getResponse(string $url) {
        $client = new \GuzzleHttp\Client(['headers' => [
            'Authorization' => "Bearer " . config('api.git_api_key')
        ]]);
        $request = new \GuzzleHttp\Psr7\Request('GET', $url);

        $promise = $client->sendAsync($request);
        $response = $promise->wait();

        return json_decode($response->getBody());
    }
    public static function getRepositories(string $username) {
        try {
            $url = self::BASE_URL . "users/$username/repos?per_page=100";
        } catch(\Exception) {
            throw new \Exception();
        }
        return self::getResponse($url);
    }

    public static function getRepository(string $username, string $repoName) {
        $url = self::BASE_URL . "repos/$username/$repoName";
        $repo = self::getResponse($url);
        $repo->branches = self::getBranches($username, $repoName);

        return $repo;
    }

    public static function getBranches(string $username, string $repoName) {
        $url = self::BASE_URL . "repos/$username/$repoName/branches";
        $branches = self::getResponse($url);

        foreach ($branches as $branch) {
            $sha = $branch->commit->sha;
            $url = self::BASE_URL . "repos/$username/$repoName/commits/$sha";
            $branch->commit->obj = self::getResponse($url);
        }

        return $branches;
    }

    public static function getTree(string $username, string $repoName, string $sha) {
        $url = self::BASE_URL . "repos/$username/$repoName/git/trees/$sha";
        return self::getResponse($url);
    }

    public static function getLastCommit(string $username, string $repoName, string $branch, string $sha) {
        $url = self::BASE_URL . "repos/$username/$repoName/commits/$branch";

        do {
            $commit = self::getResponse($url);

            foreach($commit->files as $file) {
                if($file->sha == $sha) {
                    return $commit;
                }
            }

            if(count($commit->parents) === 0) {
                break;
            }

            $url = $commit->parents[0]->url;
        } while(true);

        return null;
    }

    public static function getBlob(string $username, string $repoName, string $branch, string $sha, string $commit = null) {
        $url = self::BASE_URL . "repos/$username/$repoName/git/blobs/$sha";
        return self::getResponse($url);
    }

    public static function getCommit(string $username, string $repoName, string $sha) {
        $url = self::BASE_URL . "repos/$username/$repoName/commits/$sha";

        return self::getResponse($url);
    }
}
