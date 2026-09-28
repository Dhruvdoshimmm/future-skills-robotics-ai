(function () {
  "use strict";

  var app = angular.module("futureSkillsApp", ["ngRoute"]);

app.constant("API_BASE", "http://localhost/future-skills/api.php");

  app.config(["$routeProvider", "$locationProvider", function ($routeProvider, $locationProvider) {
    // Hash routing keeps this frontend deployable as static files on Vercel.
    $routeProvider
      .when("/", { templateUrl: "views/home.html", controller: "HomeController", title: "Home" })
      .when("/scope", { templateUrl: "views/scope.html", controller: "ScopeController", title: "Program Scope" })
      .when("/skills", { templateUrl: "views/skills.html", controller: "SkillsController", title: "Future Ready Skills" })
      .when("/faq", { templateUrl: "views/faq.html", controller: "FaqController", title: "FAQ" })
      .when("/contact", { templateUrl: "views/contact.html", controller: "ContactController", title: "Contact Us" })
      .when("/admin", { templateUrl: "views/admin.html", controller: "AdminController", title: "Admin Panel" })
      .otherwise({ redirectTo: "/" });
  }]);

  app.run(["$rootScope", "$location", function ($rootScope, $location) {
    $rootScope.$on("$routeChangeSuccess", function (event, current) {
      $rootScope.pageTitle = (current.$$route && current.$$route.title)
        ? current.$$route.title + " | Future Skills"
        : "Future Skills - Robotics & AI";
      window.scrollTo(0, 0);
    });
  }]);

  app.factory("ApiService", ["$http", "API_BASE", function ($http, API_BASE) {
    function request(method, path, data, token) {
      var config = { method: method, url: API_BASE + "?action=" + encodeURIComponent(path), data: data };
      if (token) config.headers = { Authorization: "Bearer " + token };
      return $http(config).then(function (response) {
        if (!response.data || response.data.success !== true) {
          return Promise.reject(response.data || { message: "Unexpected API response." });
        }
        return response.data;
      });
    }
    return {
      getContent: function (page) { return $http.get(API_BASE, { params: { action: "content", page: page } }).then(function (response) { if (!response.data || response.data.success !== true) return Promise.reject(response.data || { message: "Unexpected API response." }); return response.data; }); },
      login: function (email, password) { return request("POST", "admin_login", { email: email, password: password }); },
      updateContent: function (token, payload) { return request("PUT", "content", payload, token); },
      getMessages: function (token) { return request("GET", "messages", {}, token); },
      reply: function (token, payload) { return request("POST", "reply", payload, token); },
      submitContact: function (payload) { return request("POST", "contact", payload); }
    };
  }]);

  app.controller("AppController", ["$scope", function ($scope) {
    $scope.year = new Date().getFullYear();
  }]);

  app.controller("HomeController", ["$scope", "ApiService", function ($scope, ApiService) {
    $scope.loading = true;
    ApiService.getContent("home").then(function (res) {
      $scope.content = res.data;
    }).catch(function (err) {
      $scope.error = err.message || "Unable to load content.";
    }).finally(function () { $scope.loading = false; });
  }]);

  app.controller("ScopeController", ["$scope", "ApiService", function ($scope, ApiService) {
    $scope.loading = true;
    ApiService.getContent("scope").then(function (res) {
      $scope.content = res.data;
    }).catch(function (err) {
      $scope.error = err.message || "Unable to load content.";
    }).finally(function () { $scope.loading = false; });
  }]);

  app.controller("SkillsController", ["$scope", "ApiService", function ($scope, ApiService) {
    $scope.loading = true;
    ApiService.getContent("skills").then(function (res) {
      $scope.content = res.data;
    }).catch(function (err) {
      $scope.error = err.message || "Unable to load content.";
    }).finally(function () { $scope.loading = false; });
  }]);

  app.controller("FaqController", ["$scope", function ($scope) {
    $scope.faqs = [
      {
        q: "What is the school investment required for setup?",
        a: "The brochure describes a Zero Investment Model: ₹0 setup cost, no hardware purchase, and zero financial risk. Future Skills sets up the Robotics Lab free of cost."
      },
      {
        q: "What is the student fee?",
        a: "The brochure presents an affordable school model of ₹200 per student per month, described as affordable, transparent and with no hidden costs."
      },
      {
        q: "Which grades are covered?",
        a: "The program is structured for Grades 1–12, with progressive learning levels from beginner robotics and electronics through advanced Arduino and AI applications."
      },
      {
        q: "How many sessions are conducted each month?",
        a: "The program overview states 8 sessions per month, described as 2 sessions per week."
      },
      {
        q: "What technology is included?",
        a: "The brochure covers robotics, electronics, Arduino Nano and Uno, sensors, automation, IoT and AI, supported by projects and digital learning resources."
      },
      {
        q: "Does the program include assessment and certification?",
        a: "Yes. The brochure describes student evaluations, quizzes and industry-recognised certificates."
      }
    ];
  }]);

  app.controller("ContactController", ["$scope", "ApiService", function ($scope, ApiService) {
    $scope.form = {};
    $scope.submitting = false;

    $scope.submit = function () {
      if ($scope.contactForm.$invalid) return;
      $scope.submitting = true;
      $scope.success = "";
      $scope.error = "";
      ApiService.submitContact($scope.form).then(function () {
        $scope.success = "Thank you. Your request has been submitted successfully.";
        $scope.form = {};
        $scope.contactForm.$setPristine();
        $scope.contactForm.$setUntouched();
      }).catch(function (err) {
        $scope.error = err.message || "Unable to submit your request.";
      }).finally(function () {
        $scope.submitting = false;
      });
    };
  }]);

  app.controller("AdminController", ["$scope", "ApiService", function ($scope, ApiService) {
    $scope.login = {};
    $scope.token = sessionStorage.getItem("fs_admin_token") || "";
    $scope.activePage = "home";
    $scope.content = {};
    $scope.messages = [];
    $scope.loading = false;

    function loadContent() {
      $scope.loading = true;
      ApiService.getContent($scope.activePage).then(function (res) {
        $scope.content = angular.copy(res.data);
      }).catch(function (err) {
        $scope.error = err.message || "Unable to load content.";
      }).finally(function () { $scope.loading = false; });
    }

    $scope.signIn = function () {
      $scope.authBusy = true;
      $scope.error = "";
      ApiService.login($scope.login.email, $scope.login.password).then(function (res) {
        $scope.token = res.token;
        sessionStorage.setItem("fs_admin_token", res.token);
        $scope.login = {};
        loadContent();
        loadMessages();
      }).catch(function (err) {
        $scope.error = err.message || "Login failed.";
      }).finally(function () { $scope.authBusy = false; });
    };

    $scope.logout = function () {
      $scope.token = "";
      sessionStorage.removeItem("fs_admin_token");
      $scope.content = {};
      $scope.messages = [];
    };

    $scope.selectPage = function (page) {
      $scope.activePage = page;
      loadContent();
    };

    $scope.saveContent = function () {
      $scope.saveBusy = true;
      $scope.error = "";
      ApiService.updateContent($scope.token, { page: $scope.activePage, data: $scope.content })
        .then(function () {
          $scope.saved = "Content saved successfully.";
        })
        .catch(function (err) {
          $scope.error = err.message || "Unable to save content.";
        })
        .finally(function () { $scope.saveBusy = false; });
    };

    function loadMessages() {
      if (!$scope.token) return;
      ApiService.getMessages($scope.token).then(function (res) {
        $scope.messages = res.data || [];
      }).catch(function (err) {
        if (err.code === "UNAUTHORIZED") $scope.logout();
        else $scope.error = err.message || "Unable to load messages.";
      });
    }

    $scope.reply = function (message) {
      if (!message.reply_text || !message.reply_text.trim()) return;
      message.replyBusy = true;
      ApiService.reply($scope.token, {
        id: message.id,
        reply_text: message.reply_text
      }).then(function () {
        message.status = "Replied";
        message.replied_at = new Date().toISOString();
        message.reply_text = "";
      }).catch(function (err) {
        $scope.error = err.message || "Unable to save reply.";
      }).finally(function () {
        message.replyBusy = false;
      });
    };

    if ($scope.token) {
      loadContent();
      loadMessages();
    }
  }]);
})();
