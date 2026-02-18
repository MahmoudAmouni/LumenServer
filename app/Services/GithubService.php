<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GitHubService
{
    private $baseUrl;

    public function __construct(){
        $this->baseUrl =  env('GITHUB_URL');
    }


    public function getUser($username){
        $response = Http::get("{$this->baseUrl}/users/{$username}");

        if ($response->failed()) {
            return null;
        }

        $user = $response->json();

        // Keep only the fields GPT needs
        return [
            'login' => $user['login'] ?? null,
            'name' => $user['name'] ?? null,
            'avatar_url' => $user['avatar_url'] ?? null,
            'html_url' => $user['html_url'] ?? null,
            'bio' => $user['bio'] ?? null,
            'blog' => $user['blog'] ?? null,
            'location' => $user['location'] ?? null,
            'public_repos' => $user['public_repos'] ?? null,
            'followers' => $user['followers'] ?? null,
            'following' => $user['following'] ?? null,
            'created_at' => $user['created_at'] ?? null,
            'updated_at' => $user['updated_at'] ?? null,
        ];
    }

    public function getRepos($username){
        $response = Http::get("{$this->baseUrl}/users/{$username}/repos");

        if ($response->failed()) {
            return [];
        }

        $repos = $response->json();

        $cleanedRepos = array_map(function ($repo) {
            return [
                'name' => $repo['name'] ?? null,
                'full_name' => $repo['full_name'] ?? null,
                'html_url' => $repo['html_url'] ?? null,
                'description' => $repo['description'] ?? null,
                'language' => $repo['language'] ?? null,
                'created_at' => $repo['created_at'] ?? null,
                'updated_at' => $repo['updated_at'] ?? null,
                'pushed_at' => $repo['pushed_at'] ?? null,
                'stargazers_count' => $repo['stargazers_count'] ?? 0,
                'forks_count' => $repo['forks_count'] ?? 0,
                'size' => $repo['size'] ?? 0,
                'topics' => $repo['topics'] ?? [],
                'license' => $repo['license']['name'] ?? null,
            ];
        }, $repos);

        return $cleanedRepos;
    }

    public function getRepoDetails($username, $repo)
    {
        $response = Http::get("{$this->baseUrl}/repos/{$username}/{$repo}");

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }
}
