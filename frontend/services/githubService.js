// GitHub Service
import APIClient from './api.js';

export class GitHubService {
    constructor() {
        this.api = new APIClient();
    }

    async connectRepository(repoUrl, groupId, repoType = 'original') {
        return await this.api.post('/github/connect', {
            repo_url: repoUrl,
            group_id: groupId,
            repo_type: repoType
        });
    }

    async disconnectRepository(repoId) {
        return await this.api.delete(`/github/repositories/${repoId}`);
    }

    async syncRepository(repoId) {
        return await this.api.post(`/github/repositories/${repoId}/sync`);
    }

    async getRepositories() {
        return await this.api.get('/github/repositories');
    }

    async getRepositoryById(repoId) {
        return await this.api.get(`/github/repositories/${repoId}`);
    }

    async getRepositoryStats(repoId) {
        return await this.api.get(`/github/repositories/${repoId}/stats`);
    }

    async getRepositoryCommits(repoId, days = 30) {
        return await this.api.get(`/github/repositories/${repoId}/commits`, {
            params: { days }
        });
    }

    async getRepositoryPullRequests(repoId) {
        return await this.api.get(`/github/repositories/${repoId}/pull-requests`);
    }

    async getRepositoryBranches(repoId) {
        return await this.api.get(`/github/repositories/${repoId}/branches`);
    }

    async getCollaboratorRepositories(githubUsername) {
        return await this.api.get(`/github/users/${githubUsername}/repositories`);
    }

    async verifyRepositoryAccess(repoUrl) {
        return await this.api.post('/github/verify', { repo_url: repoUrl });
    }

    async getCommitActivity(repoId) {
        return await this.api.get(`/github/repositories/${repoId}/commit-activity`);
    }

    async getCodeFrequency(repoId) {
        return await this.api.get(`/github/repositories/${repoId}/code-frequency`);
    }
}

export default GitHubService;