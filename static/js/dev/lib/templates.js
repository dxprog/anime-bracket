this["Templates"] = this["Templates"] || {};

Handlebars.registerPartial("entrant", Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, functionType="function", escapeExpression=this.escapeExpression, self=this;

function program1(depth0,data) {
  
  var buffer = "", stack1;
  buffer += "\r\n            ";
  if (stack1 = helpers.votes) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.votes; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + " votes\r\n        ";
  return buffer;
  }

function program3(depth0,data) {
  
  
  return "\r\n            Pending\r\n        ";
  }

  buffer += "<dl data-id=\"";
  if (stack1 = helpers.id) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.id; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\" class=\"entrant-info\">\r\n    <dt>Headshot</dt>\r\n    <dd class=\"image\">\r\n        <img src=\"http://cdn.awwni.me/bracket/";
  if (stack1 = helpers.image) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.image; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\" alt=\"";
  if (stack1 = helpers.name) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.name; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\" />\r\n    </dd>\r\n    <dd class=\"name\">";
  if (stack1 = helpers.name) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.name; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "</dd>\r\n    <dd class=\"source\">";
  if (stack1 = helpers.source) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.source; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "</dd>\r\n    <dd class=\"votes\">\r\n        ";
  stack1 = helpers['if'].call(depth0, depth0.votes, {hash:{},inverse:self.program(3, program3, data),fn:self.program(1, program1, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\r\n    </dd>\r\n</dl>";
  return buffer;
  }));

this["Templates"]["groupPicker"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var stack1, functionType="function", escapeExpression=this.escapeExpression, self=this;

function program1(depth0,data) {
  
  var buffer = "", stack1;
  buffer += "\r\n    <li data-group=\"";
  if (stack1 = helpers.index) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.index; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\">Group ";
  if (stack1 = helpers.name) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.name; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "</li>\r\n";
  return buffer;
  }

  stack1 = helpers.each.call(depth0, depth0.groups, {hash:{},inverse:self.noop,fn:self.program(1, program1, data),data:data});
  if(stack1 || stack1 === 0) { return stack1; }
  else { return ''; }
  });

this["Templates"]["statsPopup"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, functionType="function", escapeExpression=this.escapeExpression, self=this;

function program1(depth0,data) {
  
  var buffer = "", stack1, stack2;
  buffer += "\n                    <li>\n                        <img src=\"http://cdn.awwni.me/bracket/"
    + escapeExpression(((stack1 = ((stack1 = depth0.character),stack1 == null || stack1 === false ? stack1 : stack1.image)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" alt=\""
    + escapeExpression(((stack1 = ((stack1 = depth0.character),stack1 == null || stack1 === false ? stack1 : stack1.name)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" />\n                        <span class=\"percent\">";
  if (stack2 = helpers.percent) { stack2 = stack2.call(depth0, {hash:{},data:data}); }
  else { stack2 = depth0.percent; stack2 = typeof stack2 === functionType ? stack2.apply(depth0) : stack2; }
  buffer += escapeExpression(stack2)
    + "%</span>\n                    </li>\n                ";
  return buffer;
  }

  buffer += "<div class=\"stats-popup\">\n    <dl>\n        <dt>Performance</dt>\n        <dd>Averages ";
  if (stack1 = helpers.performance) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.performance; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "% of votes per round</dd>\n        <dt>Users Also Voted For</dt>\n        <dd>\n            <ul class=\"also-voted-for\">\n                ";
  stack1 = helpers.each.call(depth0, depth0.alsoVotedFor, {hash:{},inverse:self.noop,fn:self.program(1, program1, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n            </ul>\n        </dd>\n        <dt>Also Voted for In Same Series</dt>\n        <dd>\n            <ul class=\"also-voted-for\">\n                ";
  stack1 = helpers.each.call(depth0, depth0.sameSourceVotes, {hash:{},inverse:self.noop,fn:self.program(1, program1, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\n            </ul>\n        </dd>\n    </dl>\n</div>";
  return buffer;
  });

this["Templates"]["tier"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); partials = this.merge(partials, Handlebars.partials); data = data || {};
  var buffer = "", stack1, functionType="function", escapeExpression=this.escapeExpression, self=this, helperMissing=helpers.helperMissing;

function program1(depth0,data,depth1) {
  
  var buffer = "", stack1, stack2, options;
  buffer += "\r\n        <li class=\"round";
  stack1 = helpers.unless.call(depth0, depth0.entrant2, {hash:{},inverse:self.noop,fn:self.program(2, program2, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\" data-tier=\"";
  if (stack1 = helpers.tier) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.tier; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\" data-round=\"";
  if (stack1 = helpers.id) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.id; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\">\r\n            <div data-id=\""
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant1),stack1 == null || stack1 === false ? stack1 : stack1.id)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" class=\"entrant entrant-"
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant1),stack1 == null || stack1 === false ? stack1 : stack1.position)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1));
  stack2 = helpers['if'].call(depth0, ((stack1 = depth0.entrant1),stack1 == null || stack1 === false ? stack1 : stack1.nobody), {hash:{},inverse:self.noop,fn:self.program(4, program4, data),data:data});
  if(stack2 || stack2 === 0) { buffer += stack2; }
  options = {hash:{},inverse:self.noop,fn:self.program(6, program6, data),data:data};
  stack2 = ((stack1 = helpers.userVoted || depth0.userVoted),stack1 ? stack1.call(depth0, depth0.entrant1, options) : helperMissing.call(depth0, "userVoted", depth0.entrant1, options));
  if(stack2 || stack2 === 0) { buffer += stack2; }
  buffer += "\" style=\"height:"
    + escapeExpression(((stack1 = depth1.height),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "px;\">\r\n                ";
  stack2 = self.invokePartial(partials.entrant, 'entrant', depth0.entrant1, helpers, partials, data);
  if(stack2 || stack2 === 0) { buffer += stack2; }
  buffer += "\r\n            </div>\r\n            ";
  stack2 = helpers['if'].call(depth0, depth0.entrant2, {hash:{},inverse:self.noop,fn:self.programWithDepth(8, program8, data, depth1),data:data});
  if(stack2 || stack2 === 0) { buffer += stack2; }
  buffer += "\r\n        </li>\r\n    ";
  return buffer;
  }
function program2(depth0,data) {
  
  
  return " single-entrant";
  }

function program4(depth0,data) {
  
  
  return " nobody";
  }

function program6(depth0,data) {
  
  
  return " user-voted";
  }

function program8(depth0,data,depth2) {
  
  var buffer = "", stack1, stack2, options;
  buffer += "\r\n                <div data-id=\""
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant2),stack1 == null || stack1 === false ? stack1 : stack1.id)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" class=\"entrant entrant-"
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant2),stack1 == null || stack1 === false ? stack1 : stack1.position)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + " ";
  options = {hash:{},inverse:self.noop,fn:self.program(9, program9, data),data:data};
  stack2 = ((stack1 = helpers.userVoted || depth0.userVoted),stack1 ? stack1.call(depth0, depth0.entrant2, options) : helperMissing.call(depth0, "userVoted", depth0.entrant2, options));
  if(stack2 || stack2 === 0) { buffer += stack2; }
  stack2 = helpers['if'].call(depth0, ((stack1 = depth0.entrant2),stack1 == null || stack1 === false ? stack1 : stack1.nobody), {hash:{},inverse:self.noop,fn:self.program(4, program4, data),data:data});
  if(stack2 || stack2 === 0) { buffer += stack2; }
  buffer += "\" style=\"height:"
    + escapeExpression(((stack1 = depth2.height),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "px;\">\r\n                    ";
  stack2 = self.invokePartial(partials.entrant, 'entrant', depth0.entrant2, helpers, partials, data);
  if(stack2 || stack2 === 0) { buffer += stack2; }
  buffer += "\r\n                </div>\r\n            ";
  return buffer;
  }
function program9(depth0,data) {
  
  
  return "user-voted";
  }

  buffer += "<ol class=\"tier ";
  if (stack1 = helpers.side) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.side; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\">\r\n    ";
  stack1 = helpers.each.call(depth0, depth0.rounds, {hash:{},inverse:self.noop,fn:self.programWithDepth(1, program1, data, depth0),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += "\r\n</ol>";
  return buffer;
  });

this["Templates"]["typeahead"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var stack1, functionType="function", escapeExpression=this.escapeExpression, self=this;

function program1(depth0,data) {
  
  var buffer = "", stack1;
  buffer += "\n    <li data-id=\"";
  if (stack1 = helpers.id) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.id; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\" data-index=\"";
  if (stack1 = helpers.index) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.index; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\">\n        <img src=\"";
  if (stack1 = helpers.pic) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.pic; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\" alt=\"";
  if (stack1 = helpers.name) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.name; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\" /> ";
  if (stack1 = helpers.name) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.name; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + "\n    </li>\n";
  return buffer;
  }

  stack1 = helpers.each.call(depth0, depth0, {hash:{},inverse:self.noop,fn:self.program(1, program1, data),data:data});
  if(stack1 || stack1 === 0) { return stack1; }
  else { return ''; }
  });

this["Templates"]["winner"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, stack2, functionType="function", escapeExpression=this.escapeExpression;


  buffer += "<ol class=\"tier left winner\">\r\n    <li class=\"round single-entrant\">\r\n        <div data-id=\""
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant),stack1 == null || stack1 === false ? stack1 : stack1.id)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" class=\"winner\" style=\"height:";
  if (stack2 = helpers.height) { stack2 = stack2.call(depth0, {hash:{},data:data}); }
  else { stack2 = depth0.height; stack2 = typeof stack2 === functionType ? stack2.apply(depth0) : stack2; }
  buffer += escapeExpression(stack2)
    + "px;\">\r\n            <dl>\r\n                <dt>Image</dt>\r\n                <dd><img src=\"http://cdn.awwni.me/bracket/"
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant),stack1 == null || stack1 === false ? stack1 : stack1.image)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" alt=\""
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant),stack1 == null || stack1 === false ? stack1 : stack1.name)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" /></dd>\r\n                <dt>Name</dt>\r\n                <dd><h2>"
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant),stack1 == null || stack1 === false ? stack1 : stack1.name)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "</h2></dd>\r\n            </dl>\r\n        </div>\r\n    </li>\r\n</ol>";
  return buffer;
  });