# GroupSync – Intelligent Group Project Management with Contribution Analytics

**Fairness in academic collaboration – track, measure, and visualize individual contributions in university group projects.**



---

## Table of Contents
- [Overview](#overview)
- [Problem Statement](#problem-statement)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Team & Roles](#team--roles)
- [Getting Started](#getting-started)
- [Usage](#usage)
- [Project Timeline](#project-timeline)
- [Development Approach](#development-approach)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)

---

## Overview

**GroupSync** is a web-based platform designed to improve collaboration and fairness in university group projects. In many academic projects, it becomes difficult for teachers to identify the actual contribution of each student. As a result, some students complete most of the work while others receive equal marks despite limited participation.

GroupSync solves this by providing a centralized system where students can manage group activities, track project progress, and monitor contributions. The platform integrates **GitHub activity**, **attendance records**, **working hours**, and **peer reviews** to generate a weighted contribution score for each group member.

The system includes separate dashboards for **students**, **teachers**, and **administrators**, promoting transparency, accountability, and fairness in collaborative university projects.

---

## Problem Statement

Group projects are commonly used in universities to develop teamwork skills. However, evaluating individual performance in group assignments is often difficult for teachers.

**Key challenges:**
- A small number of students complete most of the work while others contribute very little
- All students usually receive the same marks despite unequal participation
- GitHub alone cannot track communication, attendance, teamwork, or task participation
- Manual peer evaluations are often biased or incomplete

GroupSync addresses these gaps by combining multiple contribution factors into a single, fair evaluation platform.

---

## Features

### Student Features
- ✅ User Registration & Login (university email verification)
- ✅ Profile Management with profile picture upload
- ✅ Create Group with unique invitation code
- ✅ Join Group using invitation code
- ✅ Connect GitHub Repository for automatic activity tracking
- ✅ View Dashboard with contribution score (0-100) and group progress
- ✅ Submit Anonymous Peer Reviews (communication, reliability, task participation)
- ✅ View Own Contribution History (commits, attendance, logged hours, peer reviews)
- ✅ Receive Notifications (pending reviews, score drops, teacher feedback)

### Professor / Teacher Features
- ✅ Teacher Registration & Login (institutional email)
- ✅ Create Course (course code, title, semester, enrollment period)
- ✅ Enroll Students (manual or via course code)
- ✅ Create Group Assignments with evaluation parameters (weightage of metrics)
- ✅ View All Groups (size, completion status)
- ✅ Monitor Individual Contributions (0-100 score with detailed breakdown)
- ✅ Generate Reports (PDF/Excel for final grading)
- ✅ View Analytics Dashboard (contribution distribution, low performers, group comparisons)
- ✅ Identify Low Contributors (auto-flagged below threshold)
- ✅ Leave Feedback to individuals or groups

### Admin Features
- ✅ Admin Login (role-based access control)
- ✅ Manage Users (view, add, edit, delete student/teacher accounts)
- ✅ Monitor Platform Activity (system logs, active users, usage statistics)
- ✅ System Settings (email verification, session timeouts, backup schedules)
- ✅ Handle Support Requests

---

## Tech Stack

| Component | Technology |
|---|---|
| **Frontend** | HTML5, CSS (TailwindCSS), JavaScript, React.js |
| **Design & Prototyping** | Figma (wireframes, UI prototypes) |
| **Backend** | PHP (Laravel) + Node.js with Express.js (REST API architecture) |
| **Database** | MySQL (structured data storage) |
| **GitHub Integration** | GitHub REST API (commit history, repository activity, push frequency) |
| **Version Control** | Git with GitHub (6 team members as collaborators, branch protection) |
| **Deployment** | InfinityFree |
| **Project Management** | Trello (sprint planning, task assignment, progress tracking) |
| **API Documentation** | Postman |

---

## Team & Roles (Team SlackerSlayer)

| Serial | Name | Role |
|---|---|---|
| 01 | Humayra Alamgir Nuha | Team Leader, Backend Development |
| 02 | Puja Gain | Requirement Analysis, Database Design, Backend Development |
| 03 | Moumita Ghose | Front-end Development, UI/UX Design Section |
| 04 | Ritu Akter Samia | Front-end Development, Documentation |
| 05 | Sanjida Akter Mim | SQA Engineer, Backend Development, Backend Support |
| 06 | Maimuna Akter | Frontend Development, Deployment, Maintenance Tasks |

---

## Getting Started

### Prerequisites

- Git
- PHP 8.1+ with Composer
- Node.js 18+ and npm
- MySQL 8.0+
- GitHub account (for API integration)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/GroupSync.git
   cd GroupSync
   ```

2. **Backend setup (Laravel)**
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure database**
   - Create a MySQL database named `groupsync_db`
   - Update `.env` with your database credentials:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=groupsync_db
     DB_USERNAME=root
     DB_PASSWORD=
     ```

4. **Run migrations**
   ```bash
   php artisan migrate --seed
   ```

5. **Frontend setup (React)**
   ```bash
   cd ../frontend
   npm install
   cp .env.example .env
   ```

6. **GitHub API credentials**
   - Create a GitHub OAuth App at https://github.com/settings/developers
   - Add `GITHUB_CLIENT_ID` and `GITHUB_CLIENT_SECRET` to both `.env` files

7. **Run the development servers**
   ```bash
   # Backend (Laravel) - terminal 1
   cd backend
   php artisan serve

   # Frontend (React) - terminal 2
   cd frontend
   npm start
   ```

---

## Usage

After starting the services:

| Access Point | URL | Credentials (default seed) |
|---|---|---|
| Student Portal | http://localhost:3000 | student@university.edu / password |
| Teacher Portal | http://localhost:3000/teacher | teacher@university.edu / password |
| Admin Panel | http://localhost:3000/admin | admin@groupsync.com / admin123 |
| API Endpoints | http://localhost:8000/api | |
| API Docs (Postman) | Export from Postman collection | |

**Basic workflow:**

1. **Student creates a group** → receives invitation code
2. **Other students join** using the code
3. **Connect GitHub repository** to the group
4. **Students submit peer reviews** for team members
5. **System calculates contribution scores** (GitHub + attendance + peer reviews + hours)
6. **Teacher monitors dashboard** and generates reports
7. **Admin manages users** and platform settings

---

## Project Timeline (4 Weeks)

| Week | Planned Activities |
|---|---|
| 1 | Requirement analysis and system planning, Database setup and authentication system |
| 2 | Group management module development, GitHub integration and repository connection |
| 3 | Contribution tracking and scoring system, Peer review system and dashboards |
| 4 | Analytics and report generation, Testing, deployment, and documentation |

---

## Development Approach

**Agile Software Development Model** – Development is completed through multiple small iterations (sprints). Each sprint focuses on a specific module, allowing continuous testing and improvements.

**Why Agile?**
- Supports flexibility and teamwork
- Enables faster development
- Regular feedback and testing help identify problems early
- Each sprint delivers a functional module

**SDLC Process:**
1. **Requirement Analysis** – User needs and system requirements collected
2. **System Design** – Architecture, database schema, and UI design
3. **Implementation** – Frontend and backend development
4. **Testing** – Unit, integration, and system testing
5. **Deployment** – Cloud deployment for real-world access
6. **Maintenance** – Continuous updates, bug fixes, improvements

---



## API Documentation

API documentation is available via **Postman**. Key endpoints:

### Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/register` | Student/teacher signup |
| POST | `/api/auth/login` | Login |
| POST | `/api/auth/logout` | Logout |

### Groups

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/groups` | Create group |
| POST | `/api/groups/join` | Join with invitation code |
| GET | `/api/groups/{id}` | Get group details |
| PUT | `/api/groups/{id}/github` | Connect GitHub repo |

### Contributions

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/contributions/score/{userId}` | Get contribution score (0-100) |
| GET | `/api/contributions/breakdown` | Detailed score breakdown |
| POST | `/api/contributions/peer-review` | Submit anonymous peer review |

### Teacher

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/teacher/courses` | List courses |
| POST | `/api/teacher/courses` | Create new course |
| GET | `/api/teacher/groups/{courseId}` | View all groups in course |
| GET | `/api/teacher/report/{groupId}` | Generate PDF/Excel report |

---

## Testing

```bash
# Backend tests (PHPUnit)
cd backend
php artisan test

# Run specific test suite
php artisan test --filter=ContributionScoreTest

# Frontend tests (Jest + React Testing Library)
cd frontend
npm test

# End-to-end tests (if configured)
npm run test:e2e
```

**Test coverage targets:**
- Unit tests: 80%+ coverage
- Integration tests: All API endpoints
- Load testing: 100 concurrent users (Locust)

---

## Deployment

### Deploy on InfinityFree (free hosting)

1. **Build frontend**
   ```bash
   cd frontend
   npm run build
   ```

2. **Prepare backend**
   ```bash
   cd backend
   composer install --no-dev --optimize-autoloader
   ```

3. **Upload to InfinityFree**
   - Upload `frontend/build/` contents to `htdocs/`
   - Upload `backend/` contents to a subfolder (e.g., `api/`)
   - Update `.env` with InfinityFree database credentials

4. **Set up database**
   - Create MySQL database via InfinityFree control panel
   - Run migrations manually or via phpMyAdmin

### Alternative: Deploy on Railway / Vercel
- Frontend: Vercel (connect GitHub repo, auto-deploy)
- Backend: Railway (supports Laravel with MySQL)

---

## Contributing

This project is developed by **Team SlackerSlayer** for academic purposes (CSE-3104: Software Engineering and Information System Design Lab). External contributions are welcome.

**Guidelines:**
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes using Conventional Commits:
   - `feat:` new feature
   - `fix:` bug fix
   - `docs:` documentation
   - `test:` add tests
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request to `main`

---



---

## Contact

**Team SlackerSlayer** – Department of Computer Science & Engineering, University of Barishal

| Name | Role | Email |
|---|---|---|
| Humayra Alamgir Nuha | Team Leader, Backend | halamgir23.cse@bu.ac.bd |
| Puja Gain | Requirements, Database | dgain23.cse@bu.ac.bd |
| Moumita Ghose | Frontend, UI/UX | mghose23.cse@bu.ac.bd |
| Ritu Akter Samia | Frontend, Documentation | rakter23.cse@bu.ac.bd |
| Sanjida Akter Mim | SQA, Backend Support | sakter23.cse@bu.ac.bd |
| Maimuna Akter | Frontend, Deployment | maimuna22.cse@bu.ac.bd |

**Course Instructor:** Md. Samsuddoha, Assistant Professor, Department of Computer Science and Engineering, University of Barishal

**Project Repository:** [https://github.com/humayranuha/group_sync](https://github.com/humayranuha/group_sync)



---

## Acknowledgments

- University of Barishal, Department of CSE
- GitHub REST API documentation
- Laravel and React communities
- TailwindCSS for rapid UI development

---

