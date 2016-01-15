/* globals describe,it */
import Molecule from 'molecule';
import { expect } from 'chai';

import Singleton from '../../../static/js/dev/lib/singleton';

describe('Singleton tests', function() {

  it('should create an instantiated Molecule object', function() {
    let instance = Singleton('myObject', {
      method() {}
    });
    expect(instance).to.be.an('object');
    expect(instance.method).to.be.a('function');
  });

  it('should return an instance if one has already been created', function() {
    const singletonName = 'anotherObject';
    let instance = Singleton(singletonName, {
      setValue(val) {
        this.value = val;
      },
      getValue() {
        return this.value;
      }
    });
    instance.setValue(singletonName);
    instance = Singleton(singletonName);
    expect(instance.getValue()).to.equal(singletonName);
  });

  it('should instantiate a Molecule if one was passed', function() {
    const TEST_FOR_TRUTH = 'row row fight the powah';
    const myClass = Molecule({
      someValue() {
        return TEST_FOR_TRUTH;
      }
    });
    let instance = Singleton('moleculeSingleton', myClass);
    expect(instance.someValue()).to.equal(TEST_FOR_TRUTH);
  });

});