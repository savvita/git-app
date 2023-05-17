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
        $url = self::BASE_URL . "users/$username/repos?per_page=100";
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

        $blob = json_decode('{
  "sha": "a06878c49593f9806976120a0c6b5c515a0d525b",
  "node_id": "B_kwDOJZU9ktoAKGEwNjg3OGM0OTU5M2Y5ODA2OTc2MTIwYTBjNmI1YzUxNWEwZDUyNWI",
  "size": 7619,
  "url": "https://api.github.com/repos/savvita/kinopoisk/git/blobs/a06878c49593f9806976120a0c6b5c515a0d525b",
  "content": "PD9waHAKcmVxdWlyZV9vbmNlICdkYi9VT1cucGhwJzsKLyogQ3JlYXRlIGFu\nZCBVcGRhdGUgTW92aWUgKi8KLy8gICAgJHJlcyA9IG51bGw7Ci8vICAgIGlm\nKGlzc2V0KCRfUE9TVFsnc3VibWl0J10pICkgewovLyAgICAgICAgJGlkID0g\nJF9QT1NUWydpZCddID8/IDA7Ci8vICAgICAgICAkdGl0bGUgPSB0cmltKCRf\nUE9TVFsndGl0bGUnXSk7Ci8vICAgICAgICAkb3JpZ2luYWxUaXRsZSA9IHRy\naW0oJF9QT1NUWydvcmlnaW5hbFRpdGxlJ10pOwovLyAgICAgICAgJGRlc2Ny\naXB0aW9uID0gdHJpbSgkX1BPU1RbJ2Rlc2NyaXB0aW9uJ10pOwovLyAgICAg\nICAgJHllYXIgPSAkX1BPU1RbJ3llYXInXTsKLy8gICAgICAgICRkdXJhdGlv\nbiA9ICRfUE9TVFsnZHVyYXRpb24nXTsKLy8gICAgICAgICRjYXRlZ29yeUlk\nID0gJF9QT1NUWydjYXRlZ29yeUlkJ107Ci8vICAgICAgICAkcmF0ZSA9ICRf\nUE9TVFsncmF0ZSddOwovLyAgICAgICAgJHZvdGVzID0gJF9QT1NUWyd2b3Rl\ncyddOwovLyAgICAgICAgJHByZW1pdW0gPSAkX1BPU1RbJ3ByZW1pdW0nXTsK\nLy8vLyAgICAgICAgJHBlb3BsZSA9ICRfUE9TVFsnc3RhZmZfcGVyc29ucydd\nOwovLy8vICAgICAgICAkcm9sZXMgPSAkX1BPU1RbJ3N0YWZmX3JvbGVzJ107\nCi8vLy8gICAgICAgICRzdGFmZiA9IFtdOwovLy8vICAgICAgICBmb3IoJGkg\nPSAwOyAkaSA8IGNvdW50KCRwZW9wbGUpOyAkaSsrKSB7Ci8vLy8gICAgICAg\nICAgICAkc3RhZmZbXSA9IG5ldyBNb2RlbHNcRW1wbG95ZWUoMCwgbmV3IE1v\nZGVsc1xQZXJzb24oJHBlb3BsZVskaV0sICIiKSwgbmV3IFxNb2RlbHNcUm9s\nZSgkcm9sZXNbJGldLCAiIikpOwovLy8vICAgICAgICB9Ci8vICAgICAgICAk\nc3RhZmZfaWRzID0gJF9QT1NUWydzdGFmZiddOwovLyAgICAgICAgcHJpbnRf\ncigkX1BPU1RbJ3N0YWZmJ10pOwovLyAgICAgICAgJHN0YWZmID0gW107Ci8v\nICAgICAgICBmb3JlYWNoKCRzdGFmZl9pZHMgYXMgJGVtcCkgewovLyAgICAg\nICAgICAgIGlmKGVtcHR5KCRlbXApKXsKLy8gICAgICAgICAgICAgICAgY29u\ndGludWU7Ci8vICAgICAgICAgICAgfQovLyAgICAgICAgICAgICRzdGFmZltd\nID0gbmV3IFxNb2RlbHNcRW1wbG95ZWUoaW50dmFsKCRlbXApLCBuZXcgXE1v\nZGVsc1xQZXJzb24oaW50dmFsKCRlbXApLCAiIiksIG5ldyBcTW9kZWxzXFJv\nbGUoMSwgIiIpKTsKLy8gICAgICAgIH0KLy8KLy8gICAgICAgICR1b3cgPSBu\nZXcgXGRiXFVPVyhkYlxEQjo6Z2V0SW5zdGFuY2UoKSk7Ci8vICAgICAgICAk\nbW92aWUgPSBuZXcgTW9kZWxzXE1vdmllKCRpZCwgJHRpdGxlLCAkb3JpZ2lu\nYWxUaXRsZSwgJGRlc2NyaXB0aW9uLCAkeWVhciwgJGR1cmF0aW9uLCBuZXcg\nTW9kZWxzXENhdGVnb3J5KCRjYXRlZ29yeUlkLCAiIiksICRyYXRlLCAkdm90\nZXMsICRwcmVtaXVtLCBbLi4uJHN0YWZmXSk7Ci8vICAgICAgICBlY2hvICc8\ncHJlPic7Ci8vICAgICAgICBwcmludF9yKCRtb3ZpZSk7Ci8vICAgICAgICBl\nY2hvICc8L3ByZT4nOwovLyAgICAgICAgJHJlcyA9ICR1b3ctPmdldE1vdmll\ncygpLT51cGRhdGUoJG1vdmllKTsKLy8KLy8gICAgfQovKiBFbmQgQ3JlYXRl\nICovCgovKiBEZWxldGUgTW92aWUgKi8KICAgICRyZXMgPSBudWxsOwogICAg\naWYoaXNzZXQoJF9QT1NUWydzdWJtaXQnXSkgKSB7CiAgICAgICAgJGlkID0g\nJF9QT1NUWydpZCddID8/IDA7CgogICAgICAgICR1b3cgPSBuZXcgXGRiXFVP\nVyhkYlxEQjo6Z2V0SW5zdGFuY2UoKSk7CgogICAgICAgICRyZXMgPSAkdW93\nLT5nZXRNb3ZpZXMoKS0+ZGVsZXRlKCRpZCk7CgogICAgfQovKiBFbmQgQ3Jl\nYXRlICovCgokdW93ID0gbmV3IFxkYlxVT1coZGJcREI6OmdldEluc3RhbmNl\nKCkpOwogICAgJG1vdmllcyA9ICR1b3ctPmdldE1vdmllcygpLT5zZWxlY3Qo\nKTsKPz4KCgo8P3BocAovLyAgICByZXF1aXJlX29uY2UgJ2RiL1VPVy5waHAn\nOwovLwovLy8qIENyZWF0ZSAqLwovLy8vICAgICRyZXMgPSBudWxsOwovLy8v\nICAgIGlmKGlzc2V0KCRfUE9TVFsndmFsdWUnXSkgJiYgIWVtcHR5KCRfUE9T\nVFsndmFsdWUnXSkpIHsKLy8vLyAgICAgICAgJHZhbHVlID0gdHJpbSgkX1BP\nU1RbJ3ZhbHVlJ10pOwovLy8vICAgICAgICBpZiAoc3RybGVuKCR2YWx1ZSA+\nIDApICYmIHN0cmxlbigkdmFsdWUpIDwgNTApIHsKLy8vLyAgICAgICAgICAg\nICR1b3cgPSBuZXcgXGRiXFVPVyhkYlxEQjo6Z2V0SW5zdGFuY2UoKSk7Ci8v\nLy8gICAgICAgICAgICAkcmVzID0gJHVvdy0+Z2V0UGVvcGxlKCktPmNyZWF0\nZShuZXcgTW9kZWxzXFBlcnNvbigwLCAkdmFsdWUpKTsKLy8vLyAgICAgICAg\nICAgIC8vJHJlcyA9ICR1b3ctPmdldFJvbGVzKCktPmNyZWF0ZShuZXcgTW9k\nZWxzXFJvbGUoMCwgJHZhbHVlKSk7Ci8vLy8gICAgICAgICAgICAvLyRyZXMg\nPSAkdW93LT5nZXRDYXRlZ29yaWVzKCktPmNyZWF0ZShuZXcgTW9kZWxzXENh\ndGVnb3J5KDAsICR2YWx1ZSkpOwovLy8vICAgICAgICB9Ci8vLy8gICAgfQov\nLy8qIEVuZCBDcmVhdGUgKi8KLy8KLy8vKiBVcGRhdGUgKi8KLy8vLyAgICAk\ncmVzID0gbnVsbDsKLy8vLyAgICBpZihpc3NldCgkX1BPU1RbJ2lkJ10pICYm\nIGlzc2V0KCRfUE9TVFsndmFsdWUnXSkgJiYgIWVtcHR5KCRfUE9TVFsndmFs\ndWUnXSkpIHsKLy8vLyAgICAgICAgJHZhbHVlID0gdHJpbSgkX1BPU1RbJ3Zh\nbHVlJ10pOwovLy8vICAgICAgICBpZiAoc3RybGVuKCR2YWx1ZSA+IDApICYm\nIHN0cmxlbigkdmFsdWUpIDwgNTApIHsKLy8vLyAgICAgICAgICAgICR1b3cg\nPSBuZXcgXGRiXFVPVyhkYlxEQjo6Z2V0SW5zdGFuY2UoKSk7Ci8vLy8vLyAg\nICAgICAgICAgICRyZXMgPSAkdW93LT5nZXRDYXRlZ29yaWVzKCktPnVwZGF0\nZShuZXcgTW9kZWxzXENhdGVnb3J5KCRfUE9TVFsnaWQnXSwgJHZhbHVlKSk7\nCi8vLy8gICAgICAgICAgICAvLyRyZXMgPSAkdW93LT5nZXRSb2xlcygpLT51\ncGRhdGUobmV3IE1vZGVsc1xSb2xlKCRfUE9TVFsnaWQnXSwgJHZhbHVlKSk7\nCi8vLy8gICAgICAgICAgICAkcmVzID0gJHVvdy0+Z2V0UGVvcGxlKCktPnVw\nZGF0ZShuZXcgTW9kZWxzXFBlcnNvbigkX1BPU1RbJ2lkJ10sICR2YWx1ZSkp\nOwovLy8vICAgICAgICB9Ci8vLy8KLy8vLyAgICB9Ci8vCi8vLyogRW5kIFVw\nZGF0ZSAqLwovLwovLy8qIERlbGV0ZSAqLwovLyRyZXMgPSBudWxsOwovL2lm\nKGlzc2V0KCRfUE9TVFsnaWQnXSkpIHsKLy8KLy8gICAgJHVvdyA9IG5ldyBc\nZGJcVU9XKGRiXERCOjpnZXRJbnN0YW5jZSgpKTsKLy8gICAgJHJlcyA9ICR1\nb3ctPmdldFBlb3BsZSgpLT5kZWxldGUoJF9QT1NUWydpZCddKTsKLy8gICAg\nLy8kcmVzID0gJHVvdy0+Z2V0Um9sZXMoKS0+ZGVsZXRlKCRfUE9TVFsnaWQn\nXSk7Ci8vLy8gICAgJHJlcyA9ICR1b3ctPmdldENhdGVnb3JpZXMoKS0+ZGVs\nZXRlKCRfUE9TVFsnaWQnXSk7Ci8vCi8vfQovLy8qIEVuZCBEZWxldGUgKi8K\nLy8KLy8KLy8gICAgJHVvdyA9IG5ldyBcZGJcVU9XKGRiXERCOjpnZXRJbnN0\nYW5jZSgpKTsKLy8gICAgLy8kdmFsdWVzID0gJHVvdy0+Z2V0Q2F0ZWdvcmll\ncygpLT5zZWxlY3QoKTsKLy8gICAgLy8kdmFsdWVzID0gJHVvdy0+Z2V0Um9s\nZXMoKS0+c2VsZWN0KCk7Ci8vICAgICR2YWx1ZXMgPSAkdW93LT5nZXRQZW9w\nbGUoKS0+c2VsZWN0KCk7Ci8vPz4KCgo8IWRvY3R5cGUgaHRtbD4KPGh0bWwg\nbGFuZz0iZW4iPgo8aGVhZD4KICAgIDxtZXRhIGNoYXJzZXQ9IlVURi04Ij4K\nICAgIDxtZXRhIG5hbWU9InZpZXdwb3J0IgogICAgICAgICAgY29udGVudD0i\nd2lkdGg9ZGV2aWNlLXdpZHRoLCB1c2VyLXNjYWxhYmxlPW5vLCBpbml0aWFs\nLXNjYWxlPTEuMCwgbWF4aW11bS1zY2FsZT0xLjAsIG1pbmltdW0tc2NhbGU9\nMS4wIj4KICAgIDxtZXRhIGh0dHAtZXF1aXY9IlgtVUEtQ29tcGF0aWJsZSIg\nY29udGVudD0iaWU9ZWRnZSI+CiAgICA8dGl0bGU+RG9jdW1lbnQ8L3RpdGxl\nPgo8L2hlYWQ+Cjxib2R5Pgo8IS0tICAgIDxmb3JtIGFjdGlvbj0iaW5kZXgu\ncGhwIiBtZXRob2Q9IlBPU1QiPi0tPgo8IS0tICAgICAgICA8aW5wdXQgdHlw\nZT0idGV4dCIgbmFtZT0iaWQiIC8+LS0+CjwhLS0gICAgICAgIDxpbnB1dCB0\neXBlPSJ0ZXh0IiBuYW1lPSJ2YWx1ZSIgLz4tLT4KPCEtLSAgICAgICAgPGlu\ncHV0IHR5cGU9InN1Ym1pdCIgdmFsdWU9IkRlbGV0ZSIgLz4tLT4KPCEtLSAg\nICA8L2Zvcm0+LS0+CiAgICA8Zm9ybSBhY3Rpb249ImluZGV4LnBocCIgbWV0\naG9kPSJQT1NUIj4KICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0i\naWQiIHBsYWNlaG9sZGVyPSJpZCIgLz4KICAgICAgICA8aW5wdXQgdHlwZT0i\ndGV4dCIgbmFtZT0idGl0bGUiIHBsYWNlaG9sZGVyPSJ0aXRsZSIgLz4KICAg\nICAgICA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ib3JpZ2luYWxUaXRsZSIg\ncGxhY2Vob2xkZXI9Im9yaWdpbmFsVGl0bGUiIC8+CiAgICAgICAgPGlucHV0\nIHR5cGU9InRleHQiIG5hbWU9ImRlc2NyaXB0aW9uIiBwbGFjZWhvbGRlcj0i\nZGVzY3JpcHRpb24iIC8+CiAgICAgICAgPGlucHV0IHR5cGU9InRleHQiIG5h\nbWU9InllYXIiIHBsYWNlaG9sZGVyPSJ5ZWFyIiAvPgogICAgICAgIDxpbnB1\ndCB0eXBlPSJ0ZXh0IiBuYW1lPSJkdXJhdGlvbiIgcGxhY2Vob2xkZXI9ImR1\ncmF0aW9uIiAvPgogICAgICAgIDxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJj\nYXRlZ29yeUlkIiBwbGFjZWhvbGRlcj0iY2F0ZWdvcnlJZCIgLz4KICAgICAg\nICA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0icmF0ZSIgcGxhY2Vob2xkZXI9\nInJhdGUiIC8+CiAgICAgICAgPGlucHV0IHR5cGU9InRleHQiIG5hbWU9InZv\ndGVzIiBwbGFjZWhvbGRlcj0idm90ZXMiIC8+CiAgICAgICAgPGlucHV0IHR5\ncGU9ImNoZWNrYm94IiBuYW1lPSJwcmVtaXVtIiAvPiA8bGFiZWwgZm9yPSJw\ncmVtaXVtIj5QcmVtaXVtPC9sYWJlbD4KICAgICAgICA8aW5wdXQgdHlwZT0i\ndGV4dCIgbmFtZT0ic3RhZmZfcGVyc29uc1tdIiBwbGFjZWhvbGRlcj0ic3Rh\nZmYxX3BlcnNvbiIgLz4KICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgbmFt\nZT0ic3RhZmZfcm9sZXNbXSIgcGxhY2Vob2xkZXI9InN0YWZmMV9yb2xlIiAv\nPgogICAgICAgIDxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJzdGFmZl9wZXJz\nb25zW10iIHBsYWNlaG9sZGVyPSJzdGFmZjFfcGVyc29uIiAvPgogICAgICAg\nIDxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJzdGFmZl9yb2xlc1tdIiBwbGFj\nZWhvbGRlcj0ic3RhZmYxX3JvbGUiIC8+CiAgICAgICAgPGlucHV0IHR5cGU9\nInRleHQiIG5hbWU9InN0YWZmW10iIHBsYWNlaG9sZGVyPSJzdGFmZl90b191\ncGRhdGUiIC8+CiAgICAgICAgPGlucHV0IHR5cGU9InRleHQiIG5hbWU9InN0\nYWZmW10iIHBsYWNlaG9sZGVyPSJzdGFmZl90b191cGRhdGUiIC8+CiAgICAg\nICAgPGlucHV0IHR5cGU9InRleHQiIG5hbWU9InN0YWZmW10iIHBsYWNlaG9s\nZGVyPSJzdGFmZl90b191cGRhdGUiIC8+CiAgICAgICAgPGlucHV0IHR5cGU9\nInRleHQiIG5hbWU9InN0YWZmW10iIHBsYWNlaG9sZGVyPSJzdGFmZl90b191\ncGRhdGUiIC8+CgogICAgICAgIDxpbnB1dCB0eXBlPSJzdWJtaXQiIHZhbHVl\nPSJVcGRhdGUiIG5hbWU9InN1Ym1pdCIgLz4KICAgIDwvZm9ybT4KCgogICAg\nPD9waHAgaWYoJHJlcyAhPT0gbnVsbCkgOiA/PgoKICAgIDxwPlVwZGF0ZWQ8\nL3A+CiAgICA8P3BocCBlbmRpZiA/Pgo8IS0tICAgIDx0YWJsZT4tLT4KPCEt\nLSAgICAgICAgPHRoZWFkPi0tPgo8IS0tICAgICAgICA8dHI+LS0+CjwhLS0g\nICAgICAgICAgICA8dGg+SWQ8L3RoPi0tPgo8IS0tICAgICAgICAgICAgPHRo\nPlZhbHVlPC90aD4tLT4KPCEtLSAgICAgICAgPC90cj4tLT4KPCEtLSAgICAg\nICAgPC90aGVhZD4tLT4KPCEtLSAgICAgICAgPHRib2R5Pi0tPgo8IS0tICAg\nICAgICAgICAgLS0+PD9waHAgLy9mb3JlYWNoKCR2YWx1ZXMgYXMgJHZhbHVl\nKSA6ID8+CjwhLS0gICAgICAgICAgICA8dHI+LS0+CjwhLS0gICAgICAgICAg\nICAgICAgPHRkPi0tPjw/cGhwIC8vZWNobyAkdmFsdWUtPmdldElkKCk7ID8+\nPCEtLTwvdGQ+LS0+CjwhLS0gICAgICAgICAgICAgICAgPHRkPjw/cGhwIC8v\nLy9lY2hvICR2YWx1ZS0+Z2V0VmFsdWUoKTsgPz48L3RkPi0tPgo8IS0tICAg\nICAgICAgICAgICAgIDx0ZD4tLT48P3BocCAvL2VjaG8gJHZhbHVlLT5nZXRO\nYW1lKCk7ID8+PCEtLTwvdGQ+LS0+CjwhLS0gICAgICAgICAgICA8L3RyPi0t\nPgo8IS0tICAgICAgICAgICAgLS0+PD9waHAgLy9lbmRmb3JlYWNoID8+Cjwh\nLS0gICAgICAgIDwvdGJvZHk+LS0+CjwhLS0gICAgPC90YWJsZT4tLT4KICAg\nIDx0YWJsZT4KICAgICAgICA8dGhlYWQ+CiAgICAgICAgPHRyPgogICAgICAg\nICAgICA8dGg+SWQ8L3RoPgogICAgICAgICAgICA8dGg+VGl0bGU8L3RoPgog\nICAgICAgICAgICA8dGg+T3JpZ2luYWwgdGl0bGU8L3RoPgogICAgICAgICAg\nICA8dGg+RGVzY3JpcHRpb248L3RoPgogICAgICAgICAgICA8dGg+WWVhcjwv\ndGg+CiAgICAgICAgICAgIDx0aD5EdXJhdGlvbjwvdGg+CiAgICAgICAgICAg\nIDx0aD5DYXRlZ29yeTwvdGg+CiAgICAgICAgICAgIDx0aD5SYXRlPC90aD4K\nICAgICAgICAgICAgPHRoPlZvdGVzPC90aD4KICAgICAgICAgICAgPHRoPlBy\nZW1pdW08L3RoPgogICAgICAgICAgICA8dGg+U3RhZmY8L3RoPgogICAgICAg\nIDwvdHI+CiAgICAgICAgPC90aGVhZD4KICAgICAgICA8dGJvZHk+CiAgICAg\nICAgPD9waHAgZm9yZWFjaCgkbW92aWVzIGFzICRtb3ZpZSkgOiA/PgogICAg\nICAgICAgICA8dHI+CiAgICAgICAgICAgICAgICA8dGQ+PD9waHAgZWNobyAk\nbW92aWUtPmdldElkKCkgPz48L3RkPgogICAgICAgICAgICAgICAgPHRkPjw/\ncGhwIGVjaG8gJG1vdmllLT5nZXRUaXRsZSgpID8+PC90ZD4KICAgICAgICAg\nICAgICAgIDx0ZD48P3BocCBlY2hvICRtb3ZpZS0+Z2V0T3JpZ2luYWxUaXRs\nZSgpID8+PC90ZD4KICAgICAgICAgICAgICAgIDx0ZD48P3BocCBlY2hvICRt\nb3ZpZS0+Z2V0RGVzY3JpcHRpb24oKSA/PjwvdGQ+CiAgICAgICAgICAgICAg\nICA8dGQ+PD9waHAgZWNobyAkbW92aWUtPmdldFllYXIoKSA/PjwvdGQ+CiAg\nICAgICAgICAgICAgICA8dGQ+PD9waHAgZWNobyAkbW92aWUtPmdldER1cmF0\naW9uKCkgPz48L3RkPgogICAgICAgICAgICAgICAgPHRkPjw/cGhwIGVjaG8g\nJG1vdmllLT5nZXRDYXRlZ29yeSgpLT5nZXRWYWx1ZSgpID8+PC90ZD4KICAg\nICAgICAgICAgICAgIDx0ZD48P3BocCBlY2hvICRtb3ZpZS0+Z2V0UmF0ZSgp\nID8+PC90ZD4KICAgICAgICAgICAgICAgIDx0ZD48P3BocCBlY2hvICRtb3Zp\nZS0+Z2V0Vm90ZXMoKSA/PjwvdGQ+CiAgICAgICAgICAgICAgICA8dGQ+PD9w\naHAgZWNobyAkbW92aWUtPmdldFByZW1pdW0oKSA/PjwvdGQ+CiAgICAgICAg\nICAgICAgICA8dGQ+PD9waHAKICAgICAgICAgICAgICAgICAgICAkc3RhZmYg\nPSAkbW92aWUtPmdldFN0YWZmKCk7CiAgICAgICAgICAgICAgICAgICAgJHJl\ncyA9IGltcGxvZGUoJywgJywgJHN0YWZmKTsKICAgICAgICAgICAgICAgICAg\nICBlY2hvICRyZXM7CiAgICAgICAgICAgICAgICAgICAgPz4KICAgICAgICAg\nICAgICAgIDwvdGQ+CiAgICAgICAgICAgIDwvdHI+CiAgICAgICAgPD9waHAg\nZW5kZm9yZWFjaCA/PgogICAgICAgIDwvdGJvZHk+CiAgICA8L3RhYmxlPgo8\nL2JvZHk+CjwvaHRtbD4=\n",
  "encoding": "base64"
}
');

        return $blob;
    }

    public static function getCommit(string $username, string $repoName, string $sha) {
        $url = self::BASE_URL . "repos/$username/$repoName/commits/$sha";

        return self::getResponse($url);
    }
}
