this["Templates"] = this["Templates"] || {};

Handlebars.registerPartial("entrant", Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  var buffer = "", stack1, functionType="function", escapeExpression=this.escapeExpression;


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
    + "</dd>\r\n    <dd class=\"votes\">";
  if (stack1 = helpers.votes) { stack1 = stack1.call(depth0, {hash:{},data:data}); }
  else { stack1 = depth0.votes; stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1; }
  buffer += escapeExpression(stack1)
    + " votes</dd>\r\n</dl>";
  return buffer;
  }));

this["Templates"]["tier"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); partials = this.merge(partials, Handlebars.partials); data = data || {};
  var buffer = "", stack1, functionType="function", escapeExpression=this.escapeExpression, self=this, helperMissing=helpers.helperMissing;

function program1(depth0,data,depth1) {
  
  var buffer = "", stack1, stack2, options;
  buffer += "\r\n        <li class=\"round";
  stack1 = helpers.unless.call(depth0, depth0.entrant2, {hash:{},inverse:self.noop,fn:self.program(2, program2, data),data:data});
  if(stack1 || stack1 === 0) { buffer += stack1; }
  buffer += " ";
  options = {hash:{},inverse:self.noop,fn:self.program(4, program4, data),data:data};
  stack2 = ((stack1 = helpers.userVoted || depth0.userVoted),stack1 ? stack1.call(depth0, depth0.entrant1, options) : helperMissing.call(depth0, "userVoted", depth0.entrant1, options));
  if(stack2 || stack2 === 0) { buffer += stack2; }
  buffer += "\">\r\n            <div data-id=\""
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant1),stack1 == null || stack1 === false ? stack1 : stack1.id)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" class=\"entrant entrant-"
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant1),stack1 == null || stack1 === false ? stack1 : stack1.position)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" style=\"height:"
    + escapeExpression(((stack1 = depth1.height),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "px;\">\r\n                ";
  stack2 = self.invokePartial(partials.entrant, 'entrant', depth0.entrant1, helpers, partials, data);
  if(stack2 || stack2 === 0) { buffer += stack2; }
  buffer += "\r\n            </div>\r\n            ";
  stack2 = helpers['if'].call(depth0, depth0.entrant2, {hash:{},inverse:self.noop,fn:self.programWithDepth(6, program6, data, depth1),data:data});
  if(stack2 || stack2 === 0) { buffer += stack2; }
  buffer += "\r\n        </li>\r\n    ";
  return buffer;
  }
function program2(depth0,data) {
  
  
  return " single-entrant";
  }

function program4(depth0,data) {
  
  
  return "user-voted";
  }

function program6(depth0,data,depth2) {
  
  var buffer = "", stack1, stack2, options;
  buffer += "\r\n                <div data-id=\""
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant2),stack1 == null || stack1 === false ? stack1 : stack1.id)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" class=\"entrant entrant-"
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant2),stack1 == null || stack1 === false ? stack1 : stack1.position)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + " ";
  options = {hash:{},inverse:self.noop,fn:self.program(4, program4, data),data:data};
  stack2 = ((stack1 = helpers.userVoted || depth0.userVoted),stack1 ? stack1.call(depth0, depth0.entrant2, options) : helperMissing.call(depth0, "userVoted", depth0.entrant2, options));
  if(stack2 || stack2 === 0) { buffer += stack2; }
  buffer += "\" style=\"height:"
    + escapeExpression(((stack1 = depth2.height),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "px;\">\r\n                    ";
  stack2 = self.invokePartial(partials.entrant, 'entrant', depth0.entrant2, helpers, partials, data);
  if(stack2 || stack2 === 0) { buffer += stack2; }
  buffer += "\r\n                </div>\r\n            ";
  return buffer;
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
    + "px;\">\r\n            <dl>\r\n                <dt>Image</dt>\r\n                <dd><img src=\"http://cdn.awwni.me.s3.amazonaws.com/bracket/"
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant),stack1 == null || stack1 === false ? stack1 : stack1.image)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" alt=\""
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant),stack1 == null || stack1 === false ? stack1 : stack1.name)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "\" /></dd>\r\n                <dt>Name</dt>\r\n                <dd><h2>"
    + escapeExpression(((stack1 = ((stack1 = depth0.entrant),stack1 == null || stack1 === false ? stack1 : stack1.name)),typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
    + "</h2></dd>\r\n            </dl>\r\n        </div>\r\n    </li>\r\n</ol>";
  return buffer;
  });